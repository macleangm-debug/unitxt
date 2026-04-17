import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr } from "@/lib/api";
import { PageHeader, Card, Field, Input, TextArea, Select, Btn, Pill } from "@/components/UI";
import { Send } from "lucide-react";

export default function QuickSend() {
  const [channel, setChannel] = useState("sms");
  const [senderId, setSenderId] = useState("");
  const [recipients, setRecipients] = useState("");
  const [message, setMessage] = useState("");
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

  const list = recipients.split(/[\s,;\n]+/).filter(Boolean);
  const segs = Math.max(1, Math.ceil(message.length / 153));

  const submit = async (e) => {
    e.preventDefault();
    if (!senderId) return toast.error("Choose an approved sender ID first.");
    if (list.length === 0) return toast.error("Add at least one recipient.");
    if (!message.trim()) return toast.error("Type a message.");
    setBusy(true);
    try {
      const { data } = await http.post("/messaging/quick-send", {
        channel, sender_id: senderId, recipients: list, message,
        schedule_at: scheduleAt || null,
      });
      toast.success(`Dispatched · est. cost $${data.estimated_cost.toFixed(4)}`);
      setRecipients(""); setMessage("");
    } catch (err) {
      toast.error(fmtErr(err.response?.data?.detail) || err.message);
    } finally { setBusy(false); }
  };

  return (
    <div>
      <PageHeader overline="Composer" title="Quick send" desc="Type or paste recipients, write your message, and dispatch."/>

      <form onSubmit={submit} className="grid gap-6 lg:grid-cols-[1fr,360px]" data-testid="quick-send-form">
        <Card>
          <div className="grid gap-5">
            <div className="grid gap-3 sm:grid-cols-2">
              <Field label="Channel">
                <div className="grid grid-cols-2 gap-2">
                  {["sms","whatsapp"].map(c => (
                    <button type="button" key={c} onClick={()=>setChannel(c)}
                      data-testid={`channel-${c}`}
                      className={`h-10 border text-sm uppercase tracking-widest font-medium transition ${channel===c?"border-white bg-white text-black":"border-zinc-800 text-zinc-400 hover:border-zinc-600"}`}>
                      {c}
                    </button>
                  ))}
                </div>
              </Field>
              <Field label="Sender ID">
                <Select value={senderId} onChange={(e)=>setSenderId(e.target.value)} data-testid="sender-id-select">
                  <option value="">— pick approved —</option>
                  {sids.map(s => <option key={s.id} value={s.sender_id}>{s.sender_id} ({s.country})</option>)}
                </Select>
              </Field>
            </div>

            <Field label="Recipients" hint="Comma, space, or newline separated. International format e.g. +255712345678">
              <TextArea value={recipients} onChange={(e)=>setRecipients(e.target.value)}
                placeholder="+255712345678, +255700000001"
                data-testid="recipients-input"/>
            </Field>

            <Field label="Message" hint={`${message.length} chars · ${segs} segment(s)`}>
              <TextArea value={message} onChange={(e)=>setMessage(e.target.value)}
                placeholder="Hi {name}, your appointment is confirmed for tomorrow."
                data-testid="message-input"/>
            </Field>

            <Field label="Schedule (optional)">
              <Input type="datetime-local" value={scheduleAt} onChange={(e)=>setScheduleAt(e.target.value)} data-testid="schedule-input"/>
            </Field>
          </div>
        </Card>

        <div className="space-y-4">
          <Card testid="dispatch-summary">
            <div className="label-overline">Dispatch summary</div>
            <div className="mt-4 grid grid-cols-2 gap-4">
              <div>
                <div className="font-mono text-2xl font-medium">{list.length}</div>
                <div className="text-xs text-zinc-500">recipients</div>
              </div>
              <div>
                <div className="font-mono text-2xl font-medium">{segs}</div>
                <div className="text-xs text-zinc-500">segments / msg</div>
              </div>
            </div>
            <div className="mt-4 border-t border-zinc-900 pt-4 text-xs text-zinc-500">
              <div className="flex justify-between"><span>Channel</span><Pill status={channel==="sms"?"info":"approved"}>{channel.toUpperCase()}</Pill></div>
              <div className="mt-2 flex justify-between"><span>Sender ID</span><span className="font-mono text-white">{senderId || "—"}</span></div>
              <div className="mt-2 flex justify-between"><span>When</span><span className="font-mono text-white">{scheduleAt ? new Date(scheduleAt).toLocaleString() : "Now"}</span></div>
            </div>
            <Btn type="submit" disabled={busy} className="mt-5 w-full" data-testid="dispatch-btn">
              <Send className="h-4 w-4"/> {busy ? "Dispatching…" : "Dispatch"}
            </Btn>
          </Card>
          <Card>
            <div className="label-overline">Preview</div>
            <div className="mt-3 border border-zinc-800 bg-zinc-950 p-4 text-sm">
              <div className="font-mono text-[10px] uppercase tracking-widest text-zinc-500">FROM {senderId || "—"}</div>
              <div className="mt-2 whitespace-pre-wrap break-words">{message || <span className="text-zinc-600">Your message will appear here.</span>}</div>
            </div>
          </Card>
        </div>
      </form>
    </div>
  );
}
