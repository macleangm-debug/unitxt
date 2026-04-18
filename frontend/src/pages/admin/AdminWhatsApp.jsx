import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr, shortDate } from "@/lib/api";
import { PageHeader, Table, Pill, Btn, Modal, Field, TextArea } from "@/components/UI";
import { Check, X } from "lucide-react";

export default function AdminWhatsApp() {
  const [items, setItems] = useState([]);
  const [open, setOpen] = useState(null);
  const [decision, setDecision] = useState({ status:"approved", note:"" });
  const load = () => http.get("/admin/wa-templates").then(r => setItems(r.data)).catch(()=>{});
  useEffect(() => { load(); }, []);
  const review = async (e) => {
    e.preventDefault();
    try { await http.post(`/admin/wa-templates/${open.id}/review`, decision); toast.success("Reviewed"); setOpen(null); load(); }
    catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
  };
  return (
    <div>
      <PageHeader overline="WhatsApp" title="Template approvals" desc="Review client-submitted WhatsApp HSM templates."/>
      <Table testid="adm-wa-table" rows={items} columns={[
        { key:"name", label:"Name" },
        { key:"category", label:"Category", mono:true },
        { key:"language", label:"Lang", mono:true },
        { key:"body", label:"Body", render: r => <span className="text-zinc-400">{r.body?.slice(0,60)}…</span> },
        { key:"status", label:"Status", render: r => <Pill status={r.status}/> },
        { key:"created_at", label:"Submitted", mono:true, render: r => shortDate(r.created_at) },
        { key:"actions", label:"", render: r => r.status === "pending" && (
          <div className="flex gap-2">
            <button onClick={()=>{setOpen(r); setDecision({status:"approved",note:""});}} className="text-emerald-400 hover:text-emerald-300"><Check className="h-4 w-4"/></button>
            <button onClick={()=>{setOpen(r); setDecision({status:"rejected",note:""});}} className="text-red-400 hover:text-red-300"><X className="h-4 w-4"/></button>
          </div>
        )},
      ]}/>
      <Modal open={!!open} onClose={()=>setOpen(null)} title={`${decision.status==="approved"?"Approve":"Reject"} ${open?.name||""}`}>
        {open && (
          <form onSubmit={review} className="space-y-3">
            <Field label="Note (optional)"><TextArea value={decision.note} onChange={(e)=>setDecision({...decision,note:e.target.value})}/></Field>
            <Btn type="submit" className="w-full">Confirm</Btn>
          </form>
        )}
      </Modal>
    </div>
  );
}
