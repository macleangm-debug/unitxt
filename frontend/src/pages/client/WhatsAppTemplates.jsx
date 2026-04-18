import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr, shortDate } from "@/lib/api";
import { PageHeader, Card, Field, Input, TextArea, Btn, Table, Pill, Modal, Select } from "@/components/UI";
import { Plus, Trash2 } from "lucide-react";

export default function WhatsAppTemplates() {
  const [items, setItems] = useState([]);
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({ name:"", body:"", category:"utility", language:"en" });
  const load = () => http.get("/wa-templates").then(r => setItems(r.data)).catch(()=>{});
  useEffect(() => { load(); }, []);
  const add = async (e) => {
    e.preventDefault();
    try { await http.post("/wa-templates", form); setOpen(false); setForm({name:"",body:"",category:"utility",language:"en"}); load(); toast.success("Submitted for review"); }
    catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
  };
  const del = async (id) => { await http.delete(`/wa-templates/${id}`); load(); };
  return (
    <div>
      <PageHeader overline="WhatsApp" title="Message templates"
        desc="Submit WhatsApp HSM templates for approval. Approved templates can be used to send WhatsApp messages."
        actions={<Btn onClick={()=>setOpen(true)} data-testid="add-wa"><Plus className="h-4 w-4"/>New template</Btn>}/>
      <Table testid="wa-table" rows={items} empty="No WhatsApp templates yet." columns={[
        { key:"name", label:"Name", render: r => <span className="font-medium">{r.name}</span> },
        { key:"category", label:"Category", mono:true },
        { key:"language", label:"Lang", mono:true },
        { key:"body", label:"Body", render: r => <span className="text-zinc-400">{r.body.slice(0,50)}…</span> },
        { key:"status", label:"Status", render: r => <Pill status={r.status}/> },
        { key:"created_at", label:"Submitted", mono:true, render: r => shortDate(r.created_at) },
        { key:"actions", label:"", render: r => <button onClick={()=>del(r.id)} className="text-zinc-500 hover:text-red-400"><Trash2 className="h-4 w-4"/></button> },
      ]}/>
      <Modal open={open} onClose={()=>setOpen(false)} title="New WhatsApp template">
        <form onSubmit={add} className="grid gap-3 sm:grid-cols-2">
          <Field label="Name"><Input value={form.name} onChange={(e)=>setForm({...form,name:e.target.value})} required/></Field>
          <Field label="Language"><Input value={form.language} onChange={(e)=>setForm({...form,language:e.target.value})}/></Field>
          <Field label="Category">
            <Select value={form.category} onChange={(e)=>setForm({...form,category:e.target.value})}>
              <option>utility</option><option>marketing</option><option>authentication</option>
            </Select>
          </Field>
          <div className="sm:col-span-2"><Field label="Body"><TextArea value={form.body} onChange={(e)=>setForm({...form,body:e.target.value})} required/></Field></div>
          <div className="sm:col-span-2"><Btn type="submit" className="w-full">Submit for review</Btn></div>
        </form>
      </Modal>
    </div>
  );
}
