import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr } from "@/lib/api";
import { PageHeader, Card, Field, Input, TextArea, Select, Btn, Pill } from "@/components/UI";
import { Upload, FileSpreadsheet } from "lucide-react";

export default function BulkSend() {
  const [name, setName] = useState("");
  const [channel, setChannel] = useState("sms");
  const [senderId, setSenderId] = useState("");
  const [csv, setCsv] = useState("phone,name\n+255712345678,Jane\n+255700000001,John");
  const [template, setTemplate] = useState("Hi {name}, this is a personalized bulk message from us.");
  const [scheduleAt, setScheduleAt] = useState("");
  const [sids, setSids] = useState([]);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    http.get("/sender-ids").then(({data}) => {
      const approved = data.filter(s => s.status === "approved");
      setSids(approved);
      if (approved[0]) setSenderId(approved[0].sender_id);
    }).catch(() => {});
  }, []);

  const parseCsv = () => {
    const lines = csv.trim().split(/\n/);
    if (lines.length < 2) return [];
    const headers = lines[0].split(",").map(h => h.trim());
    return lines.slice(1).map(line => {
      const vals = line.split(",").map(v => v.trim());
      const row = {};
      headers.forEach((h, i) => row[h] = vals[i] || "");
      return row;
    }).filter(r => r.phone);
  };
  const recipients = parseCsv();

  const onFile = (e) => {
    const f = e.target.files?.[0];
    if (!f) return;
    const reader = new FileReader();
    reader.onload = () => setCsv(String(reader.result));
    reader.readAsText(f);
  };

  const submit = async (e) => {
    e.preventDefault();
    if (!name.trim()) return toast.error("Campaign needs a name.");
    if (!senderId) return toast.error("Choose an approved sender ID.");
    if (recipients.length === 0) return toast.error("CSV has no recipients.");
    setBusy(true);
    try {
      const { data } = await http.post("/messaging/bulk-send", {
        name, channel, sender_id: senderId,
        recipients, template,
        schedule_at: scheduleAt || null,
      });
      toast.success(`Campaign queued · est. cost $${data.estimated_cost.toFixed(4)}`);
      setName("");
    } catch (err) {
      toast.error(fmtErr(err.response?.data?.detail) || err.message);
    } finally { setBusy(false); }
  };

  return (
    <div>
      <PageHeader overline="Campaign" title="Bulk send" desc="Upload a CSV (phone, name, …) and broadcast a personalized message."/>

      <form onSubmit={submit} className="grid gap-6 lg:grid-cols-[1fr,380px]" data-testid="bulk-send-form">
        <Card>
          <div className="grid gap-5">
            <Field label="Campaign name">
              <Input value={name} onChange={(e)=>setName(e.target.value)} placeholder="January Reminders" data-testid="bulk-name"/>
            </Field>

            <div className="grid gap-3 sm:grid-cols-2">
              <Field label="Channel">
                <div className="grid grid-cols-2 gap-2">
                  {["sms","whatsapp"].map(c => (
                    <button type="button" key={c} onClick={()=>setChannel(c)}
                      data-testid={`bulk-channel-${c}`}
                      className={`h-10 border text-sm uppercase tracking-widest font-medium transition ${channel===c?"border-white bg-white text-black":"border-zinc-800 text-zinc-400 hover:border-zinc-600"}`}>{c}</button>
                  ))}
                </div>
              </Field>
              <Field label="Sender ID">
                <Select value={senderId} onChange={(e)=>setSenderId(e.target.value)} data-testid="bulk-sid">
                  <option value="">— pick approved —</option>
                  {sids.map(s => <option key={s.id} value={s.sender_id}>{s.sender_id}</option>)}
                </Select>
              </Field>
            </div>

            <Field label="CSV data" hint="First row = column headers. Must include phone column. Other columns can be merge tags.">
              <div className="mb-2 flex items-center gap-2">
                <label className="inline-flex items-center gap-2 cursor-pointer border border-zinc-800 bg-zinc-950 px-3 py-1.5 text-xs text-zinc-300 hover:border-zinc-600">
                  <FileSpreadsheet className="h-3.5 w-3.5"/> Upload .csv
                  <input type="file" accept=".csv,text/csv,text/plain" onChange={onFile} className="hidden" data-testid="bulk-file"/>
                </label>
                <span className="font-mono text-[10px] text-zinc-500">{recipients.length} valid rows parsed</span>
              </div>
              <TextArea value={csv} onChange={(e)=>setCsv(e.target.value)} className="min-h-[160px] font-mono text-xs" data-testid="bulk-csv"/>
            </Field>

            <Field label="Template" hint="Use {column_name} as merge tag. Available: phone, name, …">
              <TextArea value={template} onChange={(e)=>setTemplate(e.target.value)} data-testid="bulk-template"/>
            </Field>

            <Field label="Schedule (optional)">
              <Input type="datetime-local" value={scheduleAt} onChange={(e)=>setScheduleAt(e.target.value)} data-testid="bulk-schedule"/>
            </Field>
          </div>
        </Card>

        <div className="space-y-4">
          <Card testid="bulk-summary">
            <div className="label-overline">Campaign summary</div>
            <div className="mt-4 grid grid-cols-2 gap-4">
              <div><div className="font-mono text-2xl font-medium">{recipients.length}</div><div className="text-xs text-zinc-500">recipients</div></div>
              <div><div className="font-mono text-2xl font-medium">{Math.max(1, Math.ceil(template.length/153))}</div><div className="text-xs text-zinc-500">segments / msg</div></div>
            </div>
            <div className="mt-4 border-t border-zinc-900 pt-4 text-xs text-zinc-500">
              <div className="flex justify-between"><span>Channel</span><Pill status={channel==="sms"?"info":"approved"}>{channel.toUpperCase()}</Pill></div>
              <div className="mt-2 flex justify-between"><span>When</span><span className="font-mono text-white">{scheduleAt ? new Date(scheduleAt).toLocaleString() : "Now"}</span></div>
            </div>
            <Btn type="submit" disabled={busy} className="mt-5 w-full" data-testid="bulk-dispatch">
              <Upload className="h-4 w-4"/> {busy ? "Queuing…" : scheduleAt ? "Schedule" : "Send now"}
            </Btn>
          </Card>
          <Card>
            <div className="label-overline">Preview (row 1)</div>
            <div className="mt-3 border border-zinc-800 bg-zinc-950 p-4 text-sm">
              {recipients.length === 0 ? <span className="text-zinc-600">Add at least one row in the CSV.</span> : (
                <>
                  <div className="font-mono text-[10px] uppercase tracking-widest text-zinc-500">TO {recipients[0].phone}</div>
                  <div className="mt-2 whitespace-pre-wrap break-words">{Object.entries(recipients[0]).reduce((s,[k,v])=>s.replaceAll("{"+k+"}", v), template)}</div>
                </>
              )}
            </div>
          </Card>
        </div>
      </form>
    </div>
  );
}
