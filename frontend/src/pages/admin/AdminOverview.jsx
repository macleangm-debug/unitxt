import { useEffect, useState } from "react";
import http, { money, num, shortDate } from "@/lib/api";
import { PageHeader, Stat, Card, Pill } from "@/components/UI";
import { ResponsiveContainer, BarChart, Bar, XAxis, YAxis, Tooltip, CartesianGrid } from "recharts";

export default function AdminOverview() {
  const [data, setData] = useState(null);
  useEffect(() => { http.get("/admin/overview").then(r => setData(r.data)).catch(()=>{}); }, []);
  if (!data) return <div className="font-mono text-xs text-zinc-500">LOADING…</div>;
  const { kpi, by_country, providers, recent_activity } = data;
  return (
    <div>
      <PageHeader overline="Mission control" title="Platform overview" desc="Live KPIs across countries, providers, and resellers."/>
      <div className="grid gap-4 md:grid-cols-4">
        <Stat label="Revenue" value={money(kpi.revenue)} accent="green" testid="kpi-revenue"/>
        <Stat label="Wallet liabilities" value={money(kpi.wallet_liabilities)} sub="Funds owed to users" testid="kpi-liab"/>
        <Stat label="Messages sent" value={num(kpi.msgs_total)} sub={`${kpi.delivery_rate}% delivered`} accent="green" testid="kpi-msgs"/>
        <Stat label="Pending sender IDs" value={num(kpi.pending_sender_ids)} accent={kpi.pending_sender_ids?"orange":"white"} testid="kpi-pending"/>
      </div>
      <div className="mt-4 grid gap-4 md:grid-cols-4">
        <Stat label="Total users" value={num(kpi.users_total)}/>
        <Stat label="Clients" value={num(kpi.clients)}/>
        <Stat label="Resellers" value={num(kpi.resellers)}/>
        <Stat label="Failed messages" value={num(kpi.failed)} accent={kpi.failed?"red":"white"}/>
      </div>

      <div className="mt-6 grid gap-4 lg:grid-cols-3">
        <Card className="lg:col-span-2" testid="country-chart">
          <div className="label-overline mb-3">Users by country</div>
          <div className="h-64">
            <ResponsiveContainer>
              <BarChart data={by_country}>
                <CartesianGrid stroke="#27272A" vertical={false}/>
                <XAxis dataKey="country" stroke="#71717A" fontSize={10}/>
                <YAxis stroke="#71717A" fontSize={10}/>
                <Tooltip contentStyle={{background:"#141414", border:"1px solid #27272A", color:"#fff", fontSize:12}}/>
                <Bar dataKey="count" fill="#FFFFFF"/>
              </BarChart>
            </ResponsiveContainer>
          </div>
        </Card>

        <Card testid="provider-health">
          <div className="label-overline mb-3">Provider health</div>
          <div className="space-y-2">
            {providers.map(p => (
              <div key={p.id} className="flex items-center justify-between border border-zinc-900 bg-zinc-950 px-3 py-2">
                <div>
                  <div className="text-sm font-medium">{p.name}</div>
                  <div className="font-mono text-[10px] uppercase tracking-widest text-zinc-500">{p.type} · prio {p.priority}</div>
                </div>
                <div className="flex items-center gap-2">
                  <span className="signal-dot" style={{color: p.active ? "#34C759":"#71717A"}}/>
                  <Pill status={p.active ? "healthy":"down"}/>
                </div>
              </div>
            ))}
          </div>
        </Card>
      </div>

      <Card className="mt-6" testid="activity">
        <div className="label-overline mb-3">Recent activity</div>
        <div className="divide-y divide-zinc-900">
          {recent_activity.length === 0 && <div className="py-6 text-center text-sm text-zinc-500">No activity yet.</div>}
          {recent_activity.map(a => (
            <div key={a.id} className="flex items-center justify-between py-2.5">
              <div>
                <div className="font-mono text-xs text-zinc-300">{a.action}</div>
                <div className="text-[11px] text-zinc-500">target: {a.target || "—"}</div>
              </div>
              <div className="font-mono text-[11px] text-zinc-500">{shortDate(a.created_at)}</div>
            </div>
          ))}
        </div>
      </Card>
    </div>
  );
}
