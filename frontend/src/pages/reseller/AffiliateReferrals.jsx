import { useEffect, useState } from "react";
import http, { money, shortDate } from "@/lib/api";
import { PageHeader, Table } from "@/components/UI";

export default function AffiliateReferrals() {
  const [rows, setRows] = useState([]);
  useEffect(() => {
    http.get("/affiliate/referrals").then(r => setRows(r.data || [])).catch(() => {});
  }, []);

  return (
    <div>
      <PageHeader
        overline="Affiliate workspace"
        title="Referrals"
        desc="Everyone who signed up using your promo code, with their lifetime spend and what you've earned from them."
      />

      {/* Desktop table */}
      <div className="hidden md:block">
        <Table testid="referrals-table" rows={rows} empty="No referrals yet — share your promo code to start."
          columns={[
            { key: "email",       label: "User" },
            { key: "country",     label: "Country", mono: true },
            { key: "created_at",  label: "Signed up", mono: true, render: r => shortDate(r.created_at) },
            { key: "topup_count", label: "Top-ups",   mono: true,
              render: r => Number(r.topup_count).toLocaleString() },
            { key: "spend_usd",   label: "Lifetime spend", mono: true, render: r => money(r.spend_usd) },
            { key: "earned_from_user_usd", label: "You earned", mono: true,
              render: r => <span className="text-emerald-400">{money(r.earned_from_user_usd)}</span> },
          ]}/>
      </div>

      {/* Mobile cards */}
      <div className="space-y-2 md:hidden">
        {rows.length === 0 && (
          <div className="rounded-lg border border-dashed border-zinc-800 px-6 py-8 text-center text-sm text-zinc-500">
            No referrals yet — share your promo code to start.
          </div>
        )}
        {rows.map(r => (
          <div key={r.id} className="rounded-lg border border-zinc-800/80 bg-[#14171C] p-4" data-testid={`ref-${r.id}`}>
            <div className="flex items-start justify-between gap-3">
              <div className="min-w-0">
                <div className="truncate text-sm font-medium text-zinc-100">{r.email}</div>
                <div className="mt-0.5 text-[11px] text-zinc-500">{r.country} · {shortDate(r.created_at)}</div>
              </div>
              <div className="text-right">
                <div className="font-mono text-sm font-medium text-emerald-400">{money(r.earned_from_user_usd)}</div>
                <div className="text-[10px] uppercase tracking-wider text-zinc-500">earned</div>
              </div>
            </div>
            <div className="mt-3 grid grid-cols-2 gap-2 text-xs">
              <div>
                <div className="text-[10px] uppercase tracking-wider text-zinc-500">Top-ups</div>
                <div className="font-mono text-zinc-200">{Number(r.topup_count).toLocaleString()}</div>
              </div>
              <div>
                <div className="text-[10px] uppercase tracking-wider text-zinc-500">Lifetime spend</div>
                <div className="font-mono text-zinc-200">{money(r.spend_usd)}</div>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
