import { useEffect, useState } from "react";
import http, { creditsShort, shortDate, fmtErr } from "@/lib/api";
import { toast } from "sonner";
import {
  PageHeader, Card, Table, Pill, Stat, Btn,
} from "@/components/UI";
import { X, AlertTriangle, CheckCircle2, Clock, RefreshCw } from "lucide-react";

function CampaignDrawer({ id, onClose }) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(false);

  const load = async () => {
    setLoading(true);
    try {
      const r = await http.get(`/messaging/campaigns/${id}`);
      setData(r.data);
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
    finally { setLoading(false); }
  };
  useEffect(() => { if (id) load(); /* eslint-disable-next-line */ }, [id]);

  if (!id) return null;
  const c = data?.campaign;
  const messages = data?.messages || [];
  const failures = data?.failure_breakdown || [];
  const deliveryPct = c && c.total ? Math.round(100 * (c.delivered || 0) / c.total) : 0;

  return (
    <div className="fixed inset-0 z-[100] flex justify-end bg-black/70" onClick={onClose} data-testid="campaign-drawer">
      <div className="h-full w-full max-w-4xl overflow-y-auto border-l border-zinc-800 bg-[#0e0e0e]"
           onClick={(e) => e.stopPropagation()}>
        <div className="sticky top-0 flex items-center justify-between border-b border-zinc-900 bg-[#0e0e0e]/95 px-6 py-4 backdrop-blur">
          <div>
            <div className="label-overline">Campaign</div>
            <h2 className="mt-1 font-display text-xl font-semibold tracking-tight">
              {c?.name || "Loading..."}
            </h2>
            {c && (
              <div className="mt-0.5 text-xs text-zinc-500">
                {c.kind} · {c.channel} · sender {c.sender_id} · {c.country || "any"}
              </div>
            )}
          </div>
          <button onClick={onClose} className="text-zinc-400 hover:text-white" data-testid="camp-close">
            <X className="h-5 w-5"/>
          </button>
        </div>

        {loading && <div className="p-6 text-sm text-zinc-500">Loading…</div>}

        {c && (
          <div className="grid gap-4 p-6">
            <div className="grid grid-cols-2 gap-0 border border-zinc-900 sm:grid-cols-4">
              <Stat label="Recipients"  value={c.total || 0} accent="white"/>
              <Stat label="Delivered"   value={`${c.delivered || 0} (${deliveryPct}%)`} accent="green"/>
              <Stat label="Failed"      value={c.failed || 0} accent={(c.failed || 0) > 0 ? "orange" : "white"}/>
              <Stat label="Credits"     value={creditsShort(c.total_cost || 0)}/>
            </div>

            {/* Failure breakdown */}
            {failures.length > 0 && (
              <Card testid="failure-breakdown">
                <div className="label-overline mb-3 flex items-center gap-2">
                  <AlertTriangle className="h-3.5 w-3.5 text-amber-400"/>Why some messages failed
                </div>
                <div className="space-y-2">
                  {failures.map(f => (
                    <div key={f.code} className="flex items-center justify-between border border-zinc-900 bg-[#141414] p-3">
                      <div>
                        <div className="text-sm text-white">{f.reason}</div>
                        <div className="mt-0.5 font-mono text-[10px] text-zinc-500">{f.code}</div>
                      </div>
                      <div className="font-mono text-sm text-zinc-300">{f.count} recipient{f.count === 1 ? "" : "s"}</div>
                    </div>
                  ))}
                </div>
              </Card>
            )}

            <Card>
              <div className="mb-3 flex items-center justify-between">
                <div className="label-overline">Per-recipient delivery</div>
                <Btn variant="ghost" onClick={load} data-testid="camp-refresh">
                  <RefreshCw className="h-3 w-3"/>Refresh
                </Btn>
              </div>
              <Table
                testid="campaign-messages"
                rows={messages}
                empty="No messages yet."
                columns={[
                  { key: "to", label: "Phone", mono: true },
                  {
                    key: "status", label: "Status",
                    render: r => (
                      <span className="inline-flex items-center gap-1">
                        {r.status === "delivered" && <CheckCircle2 className="h-3 w-3 text-emerald-400"/>}
                        {r.status === "queued" && <Clock className="h-3 w-3 text-zinc-500"/>}
                        {(r.status === "failed" || r.status === "undelivered") && <AlertTriangle className="h-3 w-3 text-red-400"/>}
                        <Pill status={r.status}/>
                      </span>
                    ),
                  },
                  { key: "provider", label: "Route", mono: true,
                    render: r => (r.provider || "").slice(0, 14) || "—" },
                  {
                    key: "failure_reason", label: "Reason",
                    render: r => r.failure_reason ? (
                      <span className="text-xs text-amber-200">{r.failure_reason}</span>
                    ) : <span className="text-zinc-600">—</span>,
                  },
                  { key: "attempts", label: "Tries", mono: true,
                    render: r => r.attempts ?? 1 },
                  { key: "created_at", label: "Sent at", mono: true,
                    render: r => shortDate(r.created_at) },
                ]}
              />
            </Card>
          </div>
        )}
      </div>
    </div>
  );
}

export default function Campaigns() {
  const [items, setItems] = useState([]);
  const [open, setOpen] = useState(null);
  useEffect(() => { http.get("/messaging/campaigns").then(r => setItems(r.data)).catch(() => {}); }, []);
  return (
    <div>
      <PageHeader overline="Dispatch history" title="Campaigns"
                  desc="Every quick send, bulk send and scheduled job. Click a campaign to see every recipient — including why some didn't deliver."/>
      <Table testid="campaigns-table" rows={items}
             empty="You haven't sent any campaigns yet."
             columns={[
               {
                 key: "name", label: "Name",
                 render: r => (
                   <button onClick={() => setOpen(r.id)} className="text-left hover:text-white"
                            data-testid={`open-campaign-${r.id}`}>
                     <div className="font-medium">{r.name}</div>
                     <div className="font-mono text-[10px] uppercase tracking-widest text-zinc-500">
                       {r.kind} · {r.channel}
                     </div>
                   </button>
                 ),
               },
               { key: "total", label: "Recipients", mono: true, render: r => r.total },
               { key: "delivered", label: "Delivered", mono: true,
                 render: r => `${r.delivered}/${r.sent}` + (r.status === "running" ? ` · ${r.progress_pct || 0}%` : "") },
               { key: "failed", label: "Failed", mono: true,
                 render: r => <span className={r.failed > 0 ? "text-amber-400" : ""}>{r.failed || 0}</span> },
               { key: "total_cost", label: "Credits", mono: true, render: r => creditsShort(r.total_cost) },
               { key: "status", label: "Status", render: r => <Pill status={r.status}/> },
               { key: "created_at", label: "Created", mono: true, render: r => shortDate(r.created_at) },
             ]}/>

      <CampaignDrawer id={open} onClose={() => setOpen(null)}/>
    </div>
  );
}
