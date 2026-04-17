import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr } from "@/lib/api";
import { PageHeader, Card, Field, Input, TextArea, Btn, Table, Modal } from "@/components/UI";
import { Plus, Trash2 } from "lucide-react";

export default function Templates() {
  const [items, setItems] = useState([]);
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({ name:"", body:"", category:"transactional" });
  const load = () => http.get("/templates").then(r => setItems(r.data)).catch(()=>{});
  useEffect(() => { load(); }, []);

  const add = async (e) => {
    e.preventDefault();
    try { await http.post("/templates", form); setOpen(false); setForm({name:"",body:"",category:"transactional"}); load(); toast.success("Template saved"); }
    catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
  };
  const del = async (id) => { await http.delete(`/templates/${id}`); load(); };

  return (
    <div>
      <PageHeader overline="Reusable copy" title="Templates" desc="Save messages you reuse so quick send and bulk send stay fast."
        actions={<Btn onClick={()=>setOpen(true)} data-testid="add-template-btn"><Plus className="h-4 w-4"/>New template</Btn>}/>
      <Table testid="templates-table" rows={items} empty="No templates yet." columns={[
        { key:"name", label:"Name", render: r => <span className="font-medium">{r.name}</span> },
        { key:"category", label:"Category", mono:true },
        { key:"body", label:"Body", render: r => <span className="text-zinc-400">{r.body.slice(0,60)}{r.body.length>60?"…":""}</span> },
        { key:"actions", label:"", render: r => <button onClick={()=>del(r.id)} className="text-zinc-500 hover:text-red-400" data-testid={`del-tpl-${r.id}`}><Trash2 className="h-4 w-4"/></button> },
      ]}/>
      <Modal open={open} onClose={()=>setOpen(false)} title="New template" testid="template-modal">
        <form onSubmit={add} className="space-y-3">
          <Field label="Name"><Input value={form.name} onChange={(e)=>setForm({...form,name:e.target.value})} required data-testid="tpl-name"/></Field>
          <Field label="Body"><TextArea value={form.body} onChange={(e)=>setForm({...form,body:e.target.value})} required data-testid="tpl-body"/></Field>
          <Btn type="submit" className="w-full" data-testid="tpl-save">Save</Btn>
        </form>
      </Modal>
    </div>
  );
}
