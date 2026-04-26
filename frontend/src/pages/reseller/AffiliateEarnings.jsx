import { useEffect, useMemo, useState } from "react";
import http, { money, shortDate } from "@/lib/api";
import { PageHeader, Card, Table, Pill } from "@/components/UI";

const KIND_LABEL = {
  topup:        "Top-up commission",
  tier_bonus_1: "Tier 1 bonus",
  tier_bonus_2: "Tier 2 bonus",
};

export default function AffiliateEarnings() {
  const [rows, setRows] = useState([]);
  const [filter, setFilter] = useState("all");

  useEffect(() => {
    http.get("/affiliate/earnings").then(r => setRows(r.data || [])).catch(() => {});
  }, []);

  const totals = useMemo(() => {
    const t = { earned: 0, requested: 0, paid: 0, all: 0 };
    rows.forEach(r => {
      const v = Number(r.amount_usd) || 0;
      t[r.status] = (t[r.status] || 0) + v;
      t.all += v;
    });
    return t;
  }, [rows]);

  const visible = filter === "all" ? rows : rows.filter(r => r.status === filter);

  const filters = [
    { key: "all",       label: "All",       v: totals.all },
    { key: "earned",    label: "Available", v: totals.earned },
    { key: "requested", label: "In review", v: totals.requested },
    { key: "paid",      label: "Paid",      v: totals.paid },
  ];

  return (
    <div>
      <PageHeader
        overline="Affiliate workspace"
        title="Earnings ledger"
        desc="Every commission you've earned, with status. Click 'Available' to see what you can request a payout on right now."
      />

      <div className="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4" data-testid="earnings-filters">
        {filters.map(f => (
          <button
            key={f.key}
            onClick={() => setFilter(f.key)}
            data-testid={`earnings-tab-${f.key}`}
            className={`card-surface p-4 text-left transition ${
              filter === f.key
                ? "border-blue-500/40 bg-blue-500/[0.06] ring-1 ring-blue-500/30"
                : "hover:border-zinc-700"
            }`}
          >
            <div className="text-[10px] font-semibold uppercase tracking-wider text-zinc-500">{f.label}</div>
            <div className={`mt-1.5 font-mono text-xl font-medium ${f.key === "earned" ? "text-emerald-400" : "text-zinc-100"}`}>
              {money(f.v)}
            </div>
          </button>
        ))}
      </div>

      {/* Desktop table */}
      <div className="hidden md:block">
        <Table testid="earnings-table" rows={visible} empty="No earnings in this view." columns={[
          { key: "created_at", label: "When", mono: true, render: r => shortDate(r.created_at) },
          { key: "referred_email", label: "Referral" },
          { key: "kind", label: "Kind", render: r => KIND_LABEL[r.kind] || r.kind },
          { key: "model_used", label: "Model", mono: true,
            render: r => <span className="text-[11px] text-zinc-500">{r.model_used || "—"}</span> },
          { key: "topup_amount_usd", label: "On top-up", mono: true,
            render: r => r.topup_amount_usd ? money(r.topup_amount_usd) : "—" },
          { key: "amount_usd", label: "You earned", mono: true,
            render: r => <span className="text-emerald-400">+{money(r.amount_usd)}</span> },
          { key: "status", label: "Status", render: r =>
            <Pill status={r.status === "earned" ? "success" : r.status === "requested" ? "pending" : "info"}>{r.status}</Pill> },
        ]}/>
      </div>

      {/* Mobile cards */}
      <div className="space-y-2 md:hidden">
        {visible.length === 0 && (
          <div className="rounded-lg border border-dashed border-zinc-800 px-6 py-8 text-center text-sm text-zinc-500">
            No earnings in this view.
          </div>
        )}
        {visible.map(r => (
          <div key={r.id} className="rounded-lg border border-zinc-800/80 bg-[#14171C] p-3" data-testid={`earn-${r.id}`}>
            <div className="flex items-start justify-between gap-3">
              <div className="min-w-0 flex-1">
                <div className="truncate text-sm text-zinc-100">{r.referred_email}</div>
                <div className="mt-0.5 text-[11px] text-zinc-500">
                  {KIND_LABEL[r.kind] || r.kind} · {shortDate(r.created_at)}
                </div>
              </div>
              <div className="text-right">
                <div className="font-mono text-sm font-medium text-emerald-400">+{money(r.amount_usd)}</div>
                <Pill status={r.status === "earned" ? "success" : r.status === "requested" ? "pending" : "info"}>{r.status}</Pill>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
