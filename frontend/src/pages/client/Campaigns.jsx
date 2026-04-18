import { useEffect, useState } from "react";
import http, { creditsShort, shortDate } from "@/lib/api";
import { PageHeader, Table, Pill } from "@/components/UI";

export default function Campaigns() {
  const [items, setItems] = useState([]);
  useEffect(() => { http.get("/messaging/campaigns").then(r => setItems(r.data)).catch(()=>{}); }, []);
  return (
    <div>
      <PageHeader overline="Dispatch history" title="Campaigns" desc="Every quick send, bulk send, and scheduled job."/>
      <Table testid="campaigns-table" rows={items} columns={[
        { key: "name", label: "Name", render: r => <div><div className="font-medium">{r.name}</div><div className="font-mono text-[10px] uppercase tracking-widest text-zinc-500">{r.kind} · {r.channel}</div></div> },
        { key: "total", label: "Recipients", mono: true, render: r => r.total },
        { key: "delivered", label: "Delivered", mono: true, render: r => `${r.delivered}/${r.sent}` },
        { key: "total_cost", label: "Credits", mono: true, render: r => creditsShort(r.total_cost) },
        { key: "status", label: "Status", render: r => <Pill status={r.status}/> },
        { key: "created_at", label: "Created", mono: true, render: r => shortDate(r.created_at) },
      ]}/>
    </div>
  );
}
