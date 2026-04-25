import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import http, { money } from "@/lib/api";
import { PageHeader, Card, Table, Pill } from "@/components/UI";
import { TrendingUp, ArrowUpRight } from "lucide-react";

export default function AdminCountryPnL() {
  const [data, setData] = useState({ rows: [], total_profit_usd: 0 });

  useEffect(() => {
    http.get("/admin/country-pnl").then((r) => setData(r.data)).catch(() => {});
  }, []);

  return (
    <div>
      <PageHeader
        overline="Treasury · Per-country"
        title="Country profit & loss"
        desc="Real revenue and cost per country, derived from each delivered message's actual buy + sell price. Configure pricing in Country economics, then watch this dashboard fill in."
        actions={
          <Link
            to="/admin/country-hub"
            className="inline-flex items-center gap-1.5 text-sm text-blue-400 hover:text-blue-300"
          >
            Country hub <ArrowUpRight className="h-3.5 w-3.5"/>
          </Link>
        }
      />

      <Card className="mb-5" testid="pnl-summary">
        <div className="flex items-center gap-3">
          <div className="grid h-10 w-10 place-items-center rounded-md bg-blue-500/10 text-blue-400">
            <TrendingUp className="h-5 w-5" strokeWidth={1.8}/>
          </div>
          <div>
            <div className="text-[11px] uppercase tracking-wider text-zinc-500">Total profit (USD)</div>
            <div className="font-mono text-3xl font-medium text-zinc-100">
              {money(data.total_profit_usd)}
            </div>
          </div>
        </div>
        <p className="mt-3 max-w-3xl text-xs leading-relaxed text-zinc-500">
          Profit = Revenue − Cost (incl. VAT). For older messages without snapshot data, we fall back to current
          economics × delivered count. Update economics from <Link to="/admin/country-hub" className="text-blue-400 hover:underline">Country hub</Link>.
        </p>
      </Card>

      <Table
        testid="pnl-table"
        rows={data.rows}
        rowKey="code"
        empty="No deliveries yet — once messages start flowing, P&L will populate here."
        columns={[
          { key: "code", label: "Country", render: (r) => (
              <div className="flex items-center gap-2">
                <Pill status="info">{r.code}</Pill>
                <span className="text-zinc-300">{r.name}</span>
              </div>
          )},
          { key: "sent",          label: "Delivered",        mono: true,
            render: r => Number(r.sent).toLocaleString() },
          { key: "revenue_local", label: "Revenue (local)",  mono: true,
            render: r => `${r.currency} ${Number(r.revenue_local).toLocaleString()}` },
          { key: "cost_local",    label: "Cost (local)",     mono: true,
            render: r => `${r.currency} ${Number(r.cost_local).toLocaleString()}` },
          { key: "profit_local",  label: "Profit (local)",   mono: true,
            render: r => (
              <span className={r.profit_local >= 0 ? "text-emerald-400" : "text-red-400"}>
                {r.currency} {Number(r.profit_local).toLocaleString()}
              </span>
            )},
          { key: "margin_pct",    label: "Margin",           mono: true,
            render: r => `${r.margin_pct}%` },
          { key: "profit_usd",    label: "Profit (USD)",     mono: true,
            render: r => (
              <span className={r.profit_usd >= 0 ? "text-emerald-400" : "text-red-400"}>
                {money(r.profit_usd)}
              </span>
            )},
        ]}
      />
    </div>
  );
}
