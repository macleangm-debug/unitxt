import { useEffect, useState } from "react";
import http, { creditsShort } from "@/lib/api";
import { PageHeader, Stat, Table, Pill } from "@/components/UI";

export default function AdminWallets() {
  const [items, setItems] = useState([]);
  useEffect(() => { http.get("/admin/wallets").then(r => setItems(r.data)).catch(()=>{}); }, []);
  const total = items.reduce((s,w)=>s + Number(w.balance||0), 0);
  return (
    <div>
      <PageHeader overline="Treasury" title="All wallets" desc="Credits held across the platform."/>
      <div className="grid gap-4 md:grid-cols-3">
        <Stat label="Total credits outstanding" value={creditsShort(total)} accent="orange"/>
        <Stat label="Wallet count" value={items.length}/>
        <Stat label="Avg balance" value={creditsShort(items.length ? total/items.length : 0)}/>
      </div>
      <Table testid="wallets-table" rows={items} columns={[
        { key:"user", label:"User", render: r => <div><div className="font-medium">{r.user?.name || "—"}</div><div className="font-mono text-[10px] uppercase tracking-widest text-zinc-500">{r.user?.email}</div></div> },
        { key:"role", label:"Role", render: r => <Pill status="info">{r.user?.role || "?"}</Pill> },
        { key:"balance", label:"Credits", mono:true, render: r => <span className={r.balance>0?"text-emerald-400":""}>{creditsShort(r.balance)}</span> },
      ]}/>
    </div>
  );
}
