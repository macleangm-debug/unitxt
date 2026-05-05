import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr } from "@/lib/api";
import { PageHeader, Card, Field, Input, Btn, Table, Pill } from "@/components/UI";
import { Plus, Trash2, ShieldOff } from "lucide-react";

export default function AdminOptOut() {
  const [items, setItems] = useState([]);
  const [count, setCount] = useState(0);
  const [phone, setPhone] = useState("");
  const [reason, setReason] = useState("manual");

  const load = () => http.get("/optout").then(r => {
    setItems(r.data.items || []);
    setCount(r.data.count || 0);
  }).catch(()=>{});
  useEffect(() => { load(); }, []);

  const add = async (e) => {
    e.preventDefault();
    try {
      await http.post("/optout", { phone, reason });
      toast.success(`Added ${phone} to opt-out`);
      setPhone(""); setReason("manual"); load();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
  };

  const del = async (p) => {
    if (!window.confirm(`Remove ${p} from opt-out list?`)) return;
    try {
      await http.delete(`/optout/${encodeURIComponent(p)}`);
      toast.success("Removed"); load();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
  };

  return (
    <div>
      <PageHeader
        overline="Compliance"
        title="Opt-out list"
        desc="Phones that have opted out of receiving SMS. Sends to these numbers are silently filtered."
      />

      <div className="mb-6 grid gap-4 sm:grid-cols-3">
        <Card testid="optout-stat-total">
          <div className="label-overline">Total opted out</div>
          <div className="mt-1 font-display text-3xl font-semibold tracking-tight">{count.toLocaleString()}</div>
        </Card>
        <Card>
          <div className="label-overline">Auto-source</div>
          <div className="mt-1 text-sm text-zinc-300">
            Inbound STOP, UNSUBSCRIBE, OPTOUT or CANCEL keywords automatically
            land here via the provider inbound webhook
            (<code className="font-mono text-[11px] text-zinc-500">POST /api/optout/inbound</code>).
          </div>
        </Card>
        <Card>
          <div className="label-overline">Send-time enforcement</div>
          <div className="mt-1 text-sm text-zinc-300">
            Quick-send and bulk-send drop opt-out phones before reserving credits.
            Banned spam keywords (Settings Hub → Compliance) hard-block the campaign.
          </div>
        </Card>
      </div>

      <Card className="mb-6" testid="optout-add-card">
        <div className="mb-3 flex items-center gap-2">
          <ShieldOff className="h-4 w-4 text-zinc-400" strokeWidth={1.5}/>
          <h3 className="font-display text-lg font-semibold">Add phone manually</h3>
        </div>
        <form onSubmit={add} className="grid gap-3 sm:grid-cols-[2fr,2fr,auto] sm:items-end">
          <Field label="Phone (E.164)">
            <Input value={phone} onChange={(e)=>setPhone(e.target.value)} placeholder="+255700000123" required data-testid="optout-phone"/>
          </Field>
          <Field label="Reason">
            <Input value={reason} onChange={(e)=>setReason(e.target.value)} placeholder="manual"/>
          </Field>
          <Btn type="submit" data-testid="optout-add-btn"><Plus className="h-4 w-4"/>Add</Btn>
        </form>
      </Card>

      <Table testid="optout-table" rows={items} columns={[
        { key:"phone", label:"Phone", mono:true, render: r => r.phone },
        { key:"reason", label:"Reason", render: r => <Pill status={r.reason === "STOP keyword" ? "warning" : "info"}>{r.reason || "—"}</Pill> },
        { key:"added_at", label:"Added at", mono:true, render: r => r.added_at ? new Date(r.added_at).toLocaleString() : "—" },
        { key:"added_by", label:"Added by", mono:true, render: r => r.added_by || "—" },
        { key:"actions", label:"", render: r => (
          <button onClick={()=>del(r.phone)} className="text-zinc-400 hover:text-red-400" data-testid={`optout-del-${r.phone}`}>
            <Trash2 className="h-4 w-4"/>
          </button>
        )},
      ]}/>
    </div>
  );
}
