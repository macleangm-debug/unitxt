import { useEffect, useState } from "react";
import http, { money, shortDate } from "@/lib/api";
import { PageHeader, Table, Pill } from "@/components/UI";

export default function AdminCampaigns() {
  const [items, setItems] = useState([]);
  useEffect(() => { http.get("/admin/campaigns").then(r => setItems(r.data)).catch(()=>{}); }, []);
  return (
    <div>
      <PageHeader overline="Dispatch" title="All campaigns" desc="Every send across every workspace."/>
      <Table testid="adm-campaigns" rows={items} columns={[
        { key:"name", label:"Name" },
        { key:"channel", label:"Channel", mono:true },
        { key:"total", label:"Total", mono:true },
        { key:"delivered", label:"Delivered", mono:true, render: r => `${r.delivered}/${r.sent}` },
        { key:"total_cost", label:"Cost", mono:true, render: r => money(r.total_cost) },
        { key:"status", label:"Status", render: r => <Pill status={r.status}/> },
        { key:"created_at", label:"When", mono:true, render: r => shortDate(r.created_at) },
      ]}/>
    </div>
  );
}
