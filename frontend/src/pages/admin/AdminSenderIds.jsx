import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr, shortDate } from "@/lib/api";
import { PageHeader, Card, Btn, Table, Pill, Modal, Field, TextArea } from "@/components/UI";
import { Check, X } from "lucide-react";

export default function AdminSenderIds() {
  const [items, setItems] = useState([]);
  const [open, setOpen] = useState(null);
  const [decision, setDecision] = useState({ status:"approved", note:"" });
  const load = () => http.get("/admin/sender-ids").then(r => setItems(r.data)).catch(()=>{});
  useEffect(load, []);

  const review = async (e) => {
    e.preventDefault();
    try { await http.post(`/admin/sender-ids/${open.id}/review`, decision); toast.success("Reviewed"); setOpen(null); load(); }
    catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
  };

  return (
    <div>
      <PageHeader overline="Compliance" title="Sender IDs" desc="Review and approve client sender ID requests."/>
      <Table testid="adm-sids-table" rows={items} columns={[
        { key:"sender_id", label:"Sender ID", mono:true, render: r => <span className="font-mono text-white">{r.sender_id}</span> },
        { key:"country", label:"Country", mono:true },
        { key:"use_case", label:"Use case" },
        { key:"sample_message", label:"Sample", render: r => <span className="text-zinc-400">{r.sample_message?.slice(0,50)}…</span> },
        { key:"status", label:"Status", render: r => <Pill status={r.status}/> },
        { key:"created_at", label:"Submitted", mono:true, render: r => shortDate(r.created_at) },
        { key:"actions", label:"", render: r => r.status === "pending" && (
          <div className="flex gap-2">
            <button onClick={()=>{setOpen(r); setDecision({status:"approved",note:""});}} className="text-emerald-400 hover:text-emerald-300" data-testid={`approve-${r.id}`}><Check className="h-4 w-4"/></button>
            <button onClick={()=>{setOpen(r); setDecision({status:"rejected",note:""});}} className="text-red-400 hover:text-red-300" data-testid={`reject-${r.id}`}><X className="h-4 w-4"/></button>
          </div>
        )},
      ]}/>
      <Modal open={!!open} onClose={()=>setOpen(null)} title={`${decision.status === "approved" ? "Approve" : "Reject"} ${open?.sender_id||""}`}>
        {open && (
          <form onSubmit={review} className="space-y-3">
            <Field label="Internal note (optional)"><TextArea value={decision.note} onChange={(e)=>setDecision({...decision,note:e.target.value})}/></Field>
            <Btn type="submit" className="w-full">Confirm {decision.status}</Btn>
          </form>
        )}
      </Modal>
    </div>
  );
}
