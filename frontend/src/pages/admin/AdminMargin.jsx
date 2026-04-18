import { useEffect, useState } from "react";
import http, { money, num } from "@/lib/api";
import { PageHeader, Stat, Card, Table } from "@/components/UI";
import { ResponsiveContainer, LineChart, Line, XAxis, YAxis, Tooltip, CartesianGrid, BarChart, Bar } from "recharts";

export default function AdminMargin() {
  const [data, setData] = useState(null);
  useEffect(() => {
    http.get("/admin/reports/margin").then(r => setData(r.data)).catch(()=>{});
  }, []);
  if (!data) return <div className="font-mono text-xs text-zinc-500">LOADING…</div>;
  const t = data.totals;
  return (
    <div>
      <PageHeader overline="Financials" title="Margin & revenue"
        desc="What you earn vs what you pay providers — the full money picture."/>
      <div className="grid gap-4 md:grid-cols-4">
        <Stat label="Revenue (USD)" value={money(t.revenue_usd)} sub="Pack purchases" accent="green"/>
        <Stat label="Provider cost" value={money(t.cost_usd)} sub="USD owed carriers"/>
        <Stat label="Margin" value={money(t.margin_usd)} accent="green"/>
        <Stat label="Margin %" value={`${t.margin_pct}%`} accent={t.margin_pct>50?"green":"orange"}/>
      </div>

      <div className="mt-6 grid gap-4 lg:grid-cols-2">
        <Card testid="daily-chart">
          <div className="label-overline mb-3">Daily provider cost (USD)</div>
          <div className="h-64">
            <ResponsiveContainer>
              <LineChart data={data.daily}>
                <CartesianGrid stroke="#27272A" vertical={false}/>
                <XAxis dataKey="date" stroke="#71717A" fontSize={10}/>
                <YAxis stroke="#71717A" fontSize={10}/>
                <Tooltip contentStyle={{background:"#141414", border:"1px solid #27272A", color:"#fff", fontSize:12}}/>
                <Line type="monotone" dataKey="cost" stroke="#34C759" strokeWidth={2} dot={false}/>
              </LineChart>
            </ResponsiveContainer>
          </div>
        </Card>
        <Card testid="country-chart">
          <div className="label-overline mb-3">Messages by country</div>
          <div className="h-64">
            <ResponsiveContainer>
              <BarChart data={data.by_country}>
                <CartesianGrid stroke="#27272A" vertical={false}/>
                <XAxis dataKey="country" stroke="#71717A" fontSize={10}/>
                <YAxis stroke="#71717A" fontSize={10}/>
                <Tooltip contentStyle={{background:"#141414", border:"1px solid #27272A", color:"#fff", fontSize:12}}/>
                <Bar dataKey="msgs" fill="#FFFFFF"/>
              </BarChart>
            </ResponsiveContainer>
          </div>
        </Card>
      </div>

      <h2 className="label-overline mt-8 mb-3">By country</h2>
      <Table testid="country-table" rows={data.by_country} rowKey="country" columns={[
        { key:"country", label:"Country", mono:true },
        { key:"msgs", label:"Messages", mono:true, render: r => num(r.msgs) },
        { key:"credits", label:"Credits", mono:true, render: r => num(r.credits) },
        { key:"cost", label:"Provider cost", mono:true, render: r => money(r.cost) },
      ]}/>

      <h2 className="label-overline mt-8 mb-3">By provider</h2>
      <Table testid="provider-table" rows={data.by_provider} rowKey="provider" columns={[
        { key:"provider", label:"Provider" },
        { key:"msgs", label:"Messages", mono:true, render: r => num(r.msgs) },
        { key:"cost", label:"Cost", mono:true, render: r => money(r.cost) },
      ]}/>
    </div>
  );
}
