import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr, shortDate } from "@/lib/api";
import {
  PageHeader, Card, Field, Input, TextArea, Btn, Pill, Table, Stat,
} from "@/components/UI";
import {
  Check, X, Inbox, IdCard, MessageCircle, Users, Building2, Activity, Wallet as WalletIcon,
} from "lucide-react";

const TABS = [
  { key: "sender_ids",               label: "Sender IDs",              icon: IdCard },
  { key: "wa_templates",             label: "WhatsApp templates",      icon: MessageCircle },
  { key: "reseller_applications",    label: "Reseller applications",   icon: Users },
  { key: "institution_applications", label: "Institution applications", icon: Building2 },
  { key: "topup_requests",           label: "Top-up requests",         icon: WalletIcon },
];

/* ---------- Review drawer ---------- */
function ReviewDrawer({ item, kind, onClose, onDone }) {
  const [status, setStatus] = useState("approved");
  const [note, setNote] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => { setStatus("approved"); setNote(""); }, [item?.id]);
  if (!item) return null;

  const endpoints = {
    sender_ids:               (id) => `/admin/sender-ids/${id}/review`,
    wa_templates:             (id) => `/admin/wa-templates/${id}/review`,
    reseller_applications:    (id) => `/admin/applications/resellers/${id}/review`,
    institution_applications: (id) => `/admin/applications/institutions/${id}/review`,
    topup_requests:           (id) => `/admin/topups/${id}/review`,
  };

  const submit = async () => {
    setBusy(true);
    try {
      await http.post(endpoints[kind](item.id), { status, note });
      toast.success(`Marked ${status}`);
      onDone?.();
      onClose();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
    finally { setBusy(false); }
  };

  return (
    <div className="fixed inset-0 z-[100] flex justify-end bg-black/70" onClick={onClose} data-testid="review-drawer">
      <div className="h-full w-full max-w-xl overflow-y-auto border-l border-zinc-800 bg-[#0e0e0e]" onClick={(e) => e.stopPropagation()}>
        <div className="sticky top-0 flex items-center justify-between border-b border-zinc-900 bg-[#0e0e0e]/95 px-6 py-4 backdrop-blur">
          <div>
            <div className="label-overline">{kind.replace("_", " ")}</div>
            <h2 className="mt-1 font-display text-xl font-semibold tracking-tight">
              {item.sender_id || item.name || item.company_name || item.institution_name || "Review"}
            </h2>
          </div>
          <button onClick={onClose} className="text-zinc-400 hover:text-white">✕</button>
        </div>

        <div className="grid gap-4 p-6">
          <Card>
            <div className="label-overline mb-3">Submission details</div>
            <div className="grid gap-2 text-sm">
              {Object.entries(item)
                .filter(([k]) => !["id", "_id", "status", "created_at", "reviewed_at",
                                    "password_hash", "temp_password", "provisioned_user_id"].includes(k))
                .map(([k, v]) => (
                  <div key={k} className="grid grid-cols-[140px,1fr] gap-3 border-b border-zinc-900 py-1.5 text-zinc-400">
                    <span className="font-mono text-[11px] uppercase tracking-widest text-zinc-500">{k.replace(/_/g, " ")}</span>
                    <span className="break-words text-white">{typeof v === "object" ? JSON.stringify(v) : String(v)}</span>
                  </div>
                ))}
              {item.created_at && (
                <div className="grid grid-cols-[140px,1fr] gap-3 py-1.5 text-zinc-400">
                  <span className="font-mono text-[11px] uppercase tracking-widest text-zinc-500">submitted</span>
                  <span className="text-white">{shortDate(item.created_at)}</span>
                </div>
              )}
            </div>
          </Card>

          <Card>
            <div className="label-overline mb-3">Decision</div>
            <div className="mb-3 flex gap-2">
              <Btn variant={status === "approved" ? "primary" : "secondary"}
                   onClick={() => setStatus("approved")} data-testid="decision-approve">
                <Check className="h-4 w-4"/>Approve
              </Btn>
              <Btn variant={status === "rejected" ? "danger" : "secondary"}
                   onClick={() => setStatus("rejected")} data-testid="decision-reject">
                <X className="h-4 w-4"/>Reject
              </Btn>
            </div>
            <Field label="Internal note (optional)">
              <TextArea value={note} onChange={(e) => setNote(e.target.value)}
                         placeholder="Why? This is saved to audit log."
                         data-testid="decision-note"/>
            </Field>
            <div className="mt-4">
              <Btn onClick={submit} disabled={busy} className="w-full" data-testid="decision-submit">
                {busy ? "Saving..." : `Confirm ${status}`}
              </Btn>
            </div>
          </Card>
        </div>
      </div>
    </div>
  );
}

/* ---------- Main ---------- */
export default function AdminApprovals() {
  const [tab, setTab] = useState("sender_ids");
  const [data, setData] = useState({ sender_ids: [], wa_templates: [],
                                       reseller_applications: [], institution_applications: [],
                                       topup_requests: [],
                                       total: 0 });
  const [open, setOpen] = useState(null);
  const [loading, setLoading] = useState(false);

  const load = async () => {
    setLoading(true);
    try { const r = await http.get("/admin/approvals/inbox"); setData(r.data); }
    catch { /* noop */ }
    finally { setLoading(false); }
  };
  useEffect(() => { load(); }, []);

  const count = (key) => (data[key] || []).length;

  const columnsByKind = {
    sender_ids: [
      { key: "sender_id", label: "Sender ID", mono: true },
      { key: "country", label: "Country", mono: true },
      { key: "use_case", label: "Use case" },
      { key: "sample_message", label: "Sample",
        render: r => <span className="text-zinc-400">{(r.sample_message || "").slice(0, 50)}…</span> },
      { key: "requester_email", label: "Requester", mono: true },
      { key: "created_at", label: "Submitted", mono: true,
        render: r => shortDate(r.created_at) },
    ],
    wa_templates: [
      { key: "name", label: "Template" },
      { key: "category", label: "Category", mono: true },
      { key: "language", label: "Lang", mono: true },
      { key: "body", label: "Body",
        render: r => <span className="text-zinc-400">{(r.body || "").slice(0, 60)}…</span> },
      { key: "requester_email", label: "Requester", mono: true },
      { key: "created_at", label: "Submitted", mono: true,
        render: r => shortDate(r.created_at) },
    ],
    reseller_applications: [
      { key: "company_name", label: "Company" },
      { key: "contact_name", label: "Contact" },
      { key: "email", label: "Email", mono: true },
      { key: "country", label: "Country", mono: true },
      { key: "expected_monthly_volume", label: "Expected vol", mono: true,
        render: r => (r.expected_monthly_volume || 0).toLocaleString() },
      { key: "created_at", label: "Applied", mono: true,
        render: r => shortDate(r.created_at) },
    ],
    institution_applications: [
      { key: "institution_name", label: "Institution" },
      { key: "institution_type", label: "Type", mono: true },
      { key: "contact_name", label: "Contact" },
      { key: "email", label: "Email", mono: true },
      { key: "country", label: "Country", mono: true },
      { key: "created_at", label: "Applied", mono: true,
        render: r => shortDate(r.created_at) },
    ],
    topup_requests: [
      { key: "created_at", label: "When", mono: true, render: r => shortDate(r.created_at) },
      { key: "user_email", label: "Client", mono: true },
      { key: "user_country", label: "Country", mono: true },
      { key: "pack_name", label: "Pack", render: r => r.pack_name || "Custom amount" },
      { key: "local_amount", label: "Amount", mono: true,
        render: r => `${r.local_currency} ${Number(r.local_amount).toLocaleString()}` },
      { key: "credits_on_approval", label: "Credits on approval", mono: true,
        render: r => Number(r.credits_on_approval).toLocaleString() },
      { key: "reference", label: "Ref", mono: true, render: r => r.reference || "—" },
    ],
  };

  const columns = [
    ...columnsByKind[tab],
    {
      key: "actions", label: "",
      render: r => (
        <Btn variant="ghost" onClick={() => setOpen(r)}
             data-testid={`review-${r.id}`}>
          Review →
        </Btn>
      ),
    },
  ];

  return (
    <div>
      <PageHeader
        overline="Platform ops"
        title="Approvals"
        desc="Unified inbox — sender IDs, WhatsApp templates, reseller & institution applications. Click Review to approve or reject."
        actions={
          <Btn variant="ghost" onClick={load} disabled={loading}>
            <Activity className="h-4 w-4"/>{loading ? "Loading..." : "Refresh"}
          </Btn>
        }
      />

      <div className="mb-4 grid grid-cols-2 gap-0 border border-zinc-900 sm:grid-cols-5" data-testid="approvals-stats">
        <Stat label="Total pending"             value={data.total} accent="white"/>
        <Stat label="Sender IDs"                value={count("sender_ids")}/>
        <Stat label="WhatsApp templates"        value={count("wa_templates")}/>
        <Stat label="Reseller applications"     value={count("reseller_applications")}/>
        <Stat label="Institution applications"  value={count("institution_applications")}/>
      </div>

      <div className="mb-4 flex flex-wrap gap-2 border-b border-zinc-900 pb-3" data-testid="approval-tabs">
        {TABS.map(t => {
          const n = count(t.key);
          return (
            <button key={t.key} onClick={() => setTab(t.key)}
                    data-testid={`atab-${t.key}`}
                    className={`inline-flex items-center gap-2 border px-4 py-2 text-sm transition-all ${
                      tab === t.key ? "border-white bg-white text-black"
                                     : "border-zinc-900 bg-[#0e0e0e] text-zinc-400 hover:border-zinc-700 hover:text-white"
                    }`}>
              <t.icon className="h-4 w-4" strokeWidth={1.5}/>
              {t.label}
              {n > 0 && (
                <span className={`ml-1 grid h-5 min-w-5 place-items-center rounded-full px-1 font-mono text-[10px] ${
                  tab === t.key ? "bg-black text-white" : "bg-zinc-800 text-zinc-300"
                }`}>{n}</span>
              )}
            </button>
          );
        })}
      </div>

      {data[tab].length === 0 ? (
        <div className="flex flex-col items-center justify-center border border-dashed border-zinc-800 py-16 text-sm text-zinc-500">
          <Inbox className="mb-3 h-8 w-8 text-zinc-700" strokeWidth={1.5}/>
          Nothing to review. Nice.
        </div>
      ) : (
        <Table testid={`approvals-${tab}-table`} rows={data[tab]} columns={columns}/>
      )}

      <ReviewDrawer
        item={open}
        kind={tab}
        onClose={() => setOpen(null)}
        onDone={load}
      />
    </div>
  );
}
