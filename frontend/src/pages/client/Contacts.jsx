import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr } from "@/lib/api";
import { PageHeader, Card, Field, Input, Btn, Table, Modal } from "@/components/UI";
import { Plus, Trash2 } from "lucide-react";

export default function Contacts() {
  const [items, setItems] = useState([]);
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({ phone: "", name: "" });
  const load = () => http.get("/contacts").then(r => setItems(r.data)).catch(()=>{});
  useEffect(() => { load(); }, []);

  const add = async (e) => {
    e.preventDefault();
    try { await http.post("/contacts", form); setOpen(false); setForm({phone:"",name:""}); load(); toast.success("Contact added"); }
    catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
  };
  const del = async (id) => { await http.delete(`/contacts/${id}`); load(); };

  return (
    <div>
      <PageHeader overline="Address book" title="Contacts" desc="Manage recipients, segment with tags, and import in bulk."
        actions={<Btn onClick={()=>setOpen(true)} data-testid="add-contact-btn"><Plus className="h-4 w-4"/>Add contact</Btn>}/>
      <Table testid="contacts-table" rows={items} empty="No contacts. Add one or import from CSV." columns={[
        { key: "name", label: "Name", render: r => r.name || "—" },
        { key: "phone", label: "Phone", mono: true },
        { key: "tags", label: "Tags", render: r => (r.tags||[]).join(", ") || "—" },
        { key: "actions", label: "", render: r => <button onClick={()=>del(r.id)} className="text-zinc-500 hover:text-red-400" data-testid={`del-contact-${r.id}`}><Trash2 className="h-4 w-4"/></button> },
      ]}/>
      <Modal open={open} onClose={()=>setOpen(false)} title="Add contact" testid="add-contact-modal">
        <form onSubmit={add} className="space-y-3">
          <Field label="Phone"><Input value={form.phone} onChange={(e)=>setForm({...form,phone:e.target.value})} placeholder="+255712345678" required data-testid="contact-phone"/></Field>
          <Field label="Name"><Input value={form.name} onChange={(e)=>setForm({...form,name:e.target.value})} placeholder="Jane Doe" data-testid="contact-name"/></Field>
          <Btn type="submit" className="w-full" data-testid="contact-save">Save</Btn>
        </form>
      </Modal>
    </div>
  );
}
