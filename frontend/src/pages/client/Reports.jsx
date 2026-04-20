import { useEffect, useMemo, useState } from "react";
import http, { num } from "@/lib/api";
import { PageHeader, Stat, Card, Select, Table } from "@/components/UI";
import {
  ResponsiveContainer, BarChart, Bar, XAxis, YAxis, Tooltip, CartesianGrid,
  LineChart, Line, Legend,
} from "recharts";
import { AlertTriangle, CheckCircle2 } from "lucide-react";

const REASON_COLORS = ["#FF3B30", "#FF9F0A", "#CD8CFE", "#5AC8FA", "#FF9500", "#8E8E93", "#A1A1AA"];

export default function Reports() {
  const [days, setDays] = useState(30);
  const [data, setData] = useState(null);
  const [campaigns, setCampaigns] = useState([]);

  useEffect(() => {
    http.get(`/messaging/delivery-report?days=${days}`).then(r => setData(r.data)).catch(() => {});
    http.get("/messaging/campaigns?limit=14").then(r => setCampaigns(r.data)).catch(() => {});
  }, [days]);

  const reasonsBar = useMemo(() => (data?.failure_reasons || []).slice(0, 7).map(r => ({
    reason: r.reason.length > 30 ? r.reason.slice(0, 28) + "…" : r.reason,
    full: r.reason, count: r.count,
  })), [data]);

  const campSeries = campaigns.slice().reverse().map(c => ({
    name: c.name.slice(0, 12), delivered: c.delivered, failed: c.failed,
  }));

  return (
    <div>
      <PageHeader overline="Insights" title="Reports"
                  desc="Delivery performance, failure reasons and campaign trends over time."
                  actions={
                    <Select value={days} onChange={(e) => setDays(Number(e.target.value))} className="w-36" data-testid="report-range">
                      <option value={7}>Last 7 days</option>
                      <option value={30}>Last 30 days</option>
                      <option value={90}>Last 90 days</option>
                      <option value={365}>Last year</option>
                    </Select>
                  }/>

      {/* Headline stats */}
      <div className="mb-4 grid grid-cols-2 gap-0 border border-zinc-900 sm:grid-cols-4">
        <Stat label="Messages sent" value={num(data?.total_sent || 0)} accent="white"/>
        <Stat label="Delivered"     value={num(data?.delivered || 0)} accent="green"/>
        <Stat label="Failed"        value={num(data?.failed || 0)} accent={(data?.failed || 0) > 0 ? "orange" : "white"}/>
        <Stat label="Delivery rate" value={`${data?.delivery_rate ?? 0}%`}
              accent={(data?.delivery_rate ?? 0) >= 95 ? "green" : (data?.delivery_rate ?? 0) >= 80 ? "orange" : "red"}/>
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        {/* Delivery rate timeline */}
        <Card testid="delivery-timeline">
          <div className="label-overline mb-3">Delivery rate over time</div>
          <ResponsiveContainer width="100%" height={260}>
            <LineChart data={data?.timeline || []}>
              <CartesianGrid strokeDasharray="3 3" stroke="#27272a"/>
              <XAxis dataKey="day" stroke="#71717a" fontSize={11}/>
              <YAxis stroke="#71717a" fontSize={11} domain={[0, 100]} unit="%"/>
              <Tooltip contentStyle={{ background: "#0e0e0e", border: "1px solid #27272a" }}/>
              <Line dataKey="delivery_rate" stroke="#34C759" strokeWidth={2} dot={false} name="Delivered %"/>
            </LineChart>
          </ResponsiveContainer>
        </Card>

        {/* Sent vs failed timeline */}
        <Card testid="volume-timeline">
          <div className="label-overline mb-3">Volume — delivered vs failed</div>
          <ResponsiveContainer width="100%" height={260}>
            <BarChart data={data?.timeline || []}>
              <CartesianGrid strokeDasharray="3 3" stroke="#27272a"/>
              <XAxis dataKey="day" stroke="#71717a" fontSize={11}/>
              <YAxis stroke="#71717a" fontSize={11}/>
              <Tooltip contentStyle={{ background: "#0e0e0e", border: "1px solid #27272a" }}/>
              <Legend wrapperStyle={{ fontSize: 11 }}/>
              <Bar dataKey="delivered" stackId="a" fill="#34C759" name="Delivered"/>
              <Bar dataKey="failed"    stackId="a" fill="#FF3B30" name="Failed"/>
            </BarChart>
          </ResponsiveContainer>
        </Card>

        {/* Failure reasons */}
        <Card testid="failure-reasons" className="lg:col-span-2">
          <div className="label-overline mb-3 flex items-center gap-2">
            <AlertTriangle className="h-3.5 w-3.5 text-amber-400"/>
            Why deliveries failed
          </div>
          {reasonsBar.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-12 text-sm text-zinc-500">
              <CheckCircle2 className="mb-2 h-6 w-6 text-emerald-400"/>
              No failures in this window. Nice.
            </div>
          ) : (
            <ResponsiveContainer width="100%" height={280}>
              <BarChart data={reasonsBar} layout="vertical" margin={{ left: 140 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="#27272a"/>
                <XAxis type="number" stroke="#71717a" fontSize={11}/>
                <YAxis dataKey="reason" type="category" stroke="#71717a" fontSize={11} width={140}/>
                <Tooltip contentStyle={{ background: "#0e0e0e", border: "1px solid #27272a" }}
                          formatter={(v, _, p) => [v, p.payload.full]}/>
                <Bar dataKey="count">
                  {reasonsBar.map((_, i) => (
                    <rect key={i} fill={REASON_COLORS[i % REASON_COLORS.length]}/>
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          )}
        </Card>

        {/* By country */}
        <Card testid="by-country" className="lg:col-span-2">
          <div className="label-overline mb-3">By country</div>
          <Table rowKey="country" rows={data?.by_country || []}
                 empty="No traffic yet."
                 columns={[
                   { key: "country", label: "Country", mono: true },
                   { key: "sent", label: "Sent", mono: true, render: r => num(r.sent) },
                   { key: "delivered", label: "Delivered", mono: true, render: r => num(r.delivered) },
                   { key: "failed", label: "Failed", mono: true,
                     render: r => <span className={r.failed > 0 ? "text-amber-400" : ""}>{num(r.failed)}</span> },
                   { key: "delivery_rate", label: "Delivery rate", mono: true,
                     render: r => <span className={r.delivery_rate >= 95 ? "text-emerald-400"
                                                     : r.delivery_rate >= 80 ? "text-amber-400"
                                                     : "text-red-400"}>{r.delivery_rate}%</span> },
                 ]}/>
        </Card>

        {/* Recent campaigns */}
        <Card testid="recent-campaigns" className="lg:col-span-2">
          <div className="label-overline mb-3">Recent campaigns</div>
          <ResponsiveContainer width="100%" height={240}>
            <BarChart data={campSeries}>
              <CartesianGrid strokeDasharray="3 3" stroke="#27272a"/>
              <XAxis dataKey="name" stroke="#71717a" fontSize={11}/>
              <YAxis stroke="#71717a" fontSize={11}/>
              <Tooltip contentStyle={{ background: "#0e0e0e", border: "1px solid #27272a" }}/>
              <Legend wrapperStyle={{ fontSize: 11 }}/>
              <Bar dataKey="delivered" fill="#34C759" name="Delivered"/>
              <Bar dataKey="failed"    fill="#FF3B30" name="Failed"/>
            </BarChart>
          </ResponsiveContainer>
        </Card>
      </div>
    </div>
  );
}
