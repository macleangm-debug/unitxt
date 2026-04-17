import { useEffect, useState } from "react";
import http, { num, money } from "@/lib/api";
import { PageHeader, Stat, Card } from "@/components/UI";
import { ResponsiveContainer, BarChart, Bar, XAxis, YAxis, Tooltip, CartesianGrid, PieChart, Pie, Cell } from "recharts";

const COLORS = ["#34C759","#FF9F0A","#FF3B30","#007AFF","#A1A1AA"];

export default function Reports() {
  const [stats, setStats] = useState(null);
  const [campaigns, setCampaigns] = useState([]);
  useEffect(() => {
    Promise.all([http.get("/messaging/stats"), http.get("/messaging/campaigns?limit=14")])
      .then(([s,c])=>{ setStats(s.data); setCampaigns(c.data); }).catch(()=>{});
  }, []);
  const byStatus = Object.entries(stats?.by_status || {}).map(([k,v]) => ({ name: k, value: v.count }));
  const series = campaigns.slice().reverse().map(c => ({ name: c.name.slice(0,12), delivered: c.delivered, failed: c.failed }));
  return (
    <div>
      <PageHeader overline="Insights" title="Reports" desc="Delivery, cost, and campaign performance."/>
      <div className="grid gap-4 md:grid-cols-4">
        <Stat label="Total messages" value={num(stats?.total_messages||0)}/>
        <Stat label="Delivered" value={num(stats?.delivered||0)} accent="green"/>
        <Stat label="Failed" value={num(stats?.failed||0)} accent={stats?.failed?"red":"white"}/>
        <Stat label="Delivery rate" value={`${stats?.delivery_rate||0}%`} accent="green"/>
      </div>
      <div className="mt-6 grid gap-4 lg:grid-cols-3">
        <Card className="lg:col-span-2" testid="bar-chart">
          <div className="label-overline mb-3">Recent campaigns · delivered vs failed</div>
          <div className="h-72">
            <ResponsiveContainer>
              <BarChart data={series}>
                <CartesianGrid stroke="#27272A" vertical={false}/>
                <XAxis dataKey="name" stroke="#71717A" fontSize={10} tick={{fill:"#A1A1AA"}}/>
                <YAxis stroke="#71717A" fontSize={10} tick={{fill:"#A1A1AA"}}/>
                <Tooltip contentStyle={{background:"#141414", border:"1px solid #27272A", color:"#fff", fontSize:12}}/>
                <Bar dataKey="delivered" stackId="a" fill="#34C759"/>
                <Bar dataKey="failed" stackId="a" fill="#FF3B30"/>
              </BarChart>
            </ResponsiveContainer>
          </div>
        </Card>
        <Card testid="pie-chart">
          <div className="label-overline mb-3">Status breakdown</div>
          <div className="h-72">
            {byStatus.length === 0 ? <div className="grid h-full place-items-center text-sm text-zinc-500">Send messages to see stats.</div> : (
              <ResponsiveContainer>
                <PieChart>
                  <Pie data={byStatus} dataKey="value" nameKey="name" innerRadius={50} outerRadius={90} stroke="#0A0A0A" strokeWidth={2}>
                    {byStatus.map((_,i)=><Cell key={i} fill={COLORS[i%COLORS.length]}/>)}
                  </Pie>
                  <Tooltip contentStyle={{background:"#141414", border:"1px solid #27272A", color:"#fff", fontSize:12}}/>
                </PieChart>
              </ResponsiveContainer>
            )}
          </div>
        </Card>
      </div>
    </div>
  );
}
