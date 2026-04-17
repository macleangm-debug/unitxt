import { useEffect, useState } from "react";
import http, { shortDate } from "@/lib/api";
import { PageHeader, Table } from "@/components/UI";

export default function AdminAudit() {
  const [items, setItems] = useState([]);
  useEffect(() => { http.get("/admin/audit-logs").then(r => setItems(r.data)).catch(()=>{}); }, []);
  return (
    <div>
      <PageHeader overline="Compliance" title="Audit logs" desc="Immutable trail of every privileged action."/>
      <Table testid="audit-table" rows={items} columns={[
        { key:"created_at", label:"When", mono:true, render: r => shortDate(r.created_at) },
        { key:"action", label:"Action", mono:true },
        { key:"actor_id", label:"Actor", mono:true, render: r => r.actor_id?.slice(0,8) || "—" },
        { key:"target", label:"Target", mono:true, render: r => r.target?.slice(0,12) || "—" },
        { key:"meta", label:"Meta", render: r => <pre className="font-mono text-[10px] text-zinc-500">{JSON.stringify(r.meta).slice(0,80)}</pre> },
      ]}/>
    </div>
  );
}
