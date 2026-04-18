import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr, money } from "@/lib/api";
import { PageHeader, Field, Input, Select, Btn, Table, Pill, Modal } from "@/components/UI";
import { Plus, Trash2 } from "lucide-react";

const empty = { name:"", credits:1000, price_usd:15, tag:"", active:true };

export default function AdminCreditPacks() {
  const [items, setItems] = useState([]);
  const [edit, setEdit] = useState(null);
  const load = () => http.get("/admin/credit-packs").then(r => setItems(r.data)).catch(()=>{});
  useEffect(() => { load(); }, []);
  const save = async (e) => {
    e.preventDefault();
    const body = { ...edit, credits: Number(edit.credits), price_usd: Number(edit.price_usd) };
    try {
      if (edit.id) await http.patch(`/admin/credit-packs/${edit.id}`, body);
      else await http.post("/admin/credit-packs", body);
      toast.success("Saved"); setEdit(null); load();
    } catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
  };
  const del = async (id) => { if(!window.confirm("Delete pack?")) return; await http.delete(`/admin/credit-packs/${id}`); load(); };
  return (
    <div>
      <PageHeader overline="Catalog" title="Credit packs"
        desc="The pricing ladder your clients see. Resellers can buy packs too."
        actions={<Btn onClick={()=>setEdit({...empty})} data-testid="add-pack"><Plus className="h-4 w-4"/>Add pack</Btn>}/>
      <Table testid="packs-table" rows={items} columns={[
        { key:"name", label:"Name" },
        { key:"tag", label:"Tag", mono:true },
        { key:"credits", label:"Credits", mono:true, render: r => Number(r.credits).toLocaleString() },
        { key:"price_usd", label:"Price", mono:true, render: r => money(r.price_usd) },
        { key:"rate", label:"$/credit", mono:true, render: r => `$${(r.price_usd/r.credits).toFixed(4)}` },
        { key:"active", label:"Status", render: r => <Pill status={r.active?"active":"down"}/> },
        { key:"actions", label:"", render: r => (
          <div className="flex gap-2">
            <button onClick={()=>setEdit({...r})} className="text-zinc-400 hover:text-white text-xs underline">edit</button>
            <button onClick={()=>del(r.id)} className="text-zinc-400 hover:text-red-400"><Trash2 className="h-4 w-4"/></button>
          </div>
        )},
      ]}/>
      <Modal open={!!edit} onClose={()=>setEdit(null)} title={edit?.id?"Edit pack":"New pack"}>
        {edit && (
          <form onSubmit={save} className="grid gap-3 sm:grid-cols-2">
            <Field label="Name"><Input value={edit.name} onChange={(e)=>setEdit({...edit,name:e.target.value})} required/></Field>
            <Field label="Tag (badge)"><Input value={edit.tag||""} onChange={(e)=>setEdit({...edit,tag:e.target.value})}/></Field>
            <Field label="Credits"><Input type="number" value={edit.credits} onChange={(e)=>setEdit({...edit,credits:e.target.value})} required/></Field>
            <Field label="Price (USD)"><Input type="number" step="any" value={edit.price_usd} onChange={(e)=>setEdit({...edit,price_usd:e.target.value})} required/></Field>
            <Field label="Active">
              <Select value={edit.active?"1":"0"} onChange={(e)=>setEdit({...edit,active:e.target.value==="1"})}>
                <option value="1">active</option><option value="0">inactive</option>
              </Select>
            </Field>
            <div className="sm:col-span-2"><Btn type="submit" className="w-full">Save</Btn></div>
          </form>
        )}
      </Modal>
    </div>
  );
}
