import { useEffect, useState } from "react";
import http from "@/lib/api";
import { PageHeader, Card, Table, Pill } from "@/components/UI";
import { Percent, ShieldCheck, Info } from "lucide-react";

export default function ResellerPricing() {
  const [data, setData] = useState({ default_commission_rate: 0, overrides: [] });
  useEffect(() => {
    http.get("/reseller/pricing").then(r => setData(r.data)).catch(() => {});
  }, []);

  const pct = (x) => `${(Number(x || 0) * 100).toFixed(1)}%`;

  return (
    <div>
      <PageHeader
        overline="Your business"
        title="Commission"
        desc="Clients always pay the global retail rate. You earn a commission out of the platform's margin on every send your clients make — admin controls the rate."
      />

      <div className="grid gap-4 lg:grid-cols-[2fr,3fr]">
        <Card testid="commission-default">
          <div className="flex items-start gap-3">
            <div className="grid h-11 w-11 place-items-center border border-zinc-800 bg-[#141414]">
              <Percent className="h-5 w-5 text-white" strokeWidth={1.5}/>
            </div>
            <div className="flex-1">
              <div className="label-overline">Default commission</div>
              <div className="mt-1 font-display text-4xl font-semibold tracking-tight" data-testid="default-commission-rate">
                {pct(data.default_commission_rate)}
              </div>
              <p className="mt-2 text-xs text-zinc-500">
                Applied to every send from your clients, across all countries &amp; channels, unless an override is set below.
              </p>
            </div>
          </div>

          <div className="mt-5 flex items-start gap-2 border border-zinc-900 bg-[#0e0e0e] p-3">
            <ShieldCheck className="mt-0.5 h-4 w-4 shrink-0 text-emerald-400" strokeWidth={1.5}/>
            <div className="text-xs text-zinc-400">
              <span className="font-medium text-zinc-200">Loss-proof</span> — commission is paid
              from platform revenue the instant a client send succeeds, credited directly to your
              float wallet. Clients are never overcharged.
            </div>
          </div>

          <div className="mt-3 flex items-start gap-2 border border-zinc-900 bg-[#0e0e0e] p-3">
            <Info className="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" strokeWidth={1.5}/>
            <div className="text-xs text-zinc-500">
              Need a different rate? Contact your account manager — admin controls commission tiers.
            </div>
          </div>
        </Card>

        <Card testid="commission-overrides">
          <div className="label-overline mb-3">Per country &amp; channel overrides</div>
          <Table
            testid="commission-table"
            rows={data.overrides || []}
            empty="No overrides — default commission applies across the board."
            columns={[
              { key: "country", label: "Country", mono: true },
              { key: "channel", label: "Channel", mono: true },
              {
                key: "commission_rate",
                label: "Commission",
                mono: true,
                render: r => pct(r.commission_rate),
              },
              {
                key: "active",
                label: "Status",
                render: r => <Pill status={r.active ? "active" : "down"}/>,
              },
            ]}
          />
        </Card>
      </div>
    </div>
  );
}
