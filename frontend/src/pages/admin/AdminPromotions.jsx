import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr } from "@/lib/api";
import { PageHeader, Field, Input, Select, Btn, Table, Pill, Modal } from "@/components/UI";
import { Plus, Trash2 } from "lucide-react";

const empty = { name:"", code:"", type:"bonus_credit", value:10, min_topup:0, active:true };

export default function AdminPromotions() {
  const [items, setItems] = useState([]);
  const [edit, setEdit] = useState(null);
  const load = () => http.get("/admin/promotions").then(r => setItems(r.data)).catch(()=>{});
  useEffect(load, []);
  const save = async (e) => {
    e.preventDefault();
    const body = { ...edit, value:Number(edit.value), min_topup:Number(edit.min_topup) };
    try {
      if (edit.id) await http.patch(`/admin/promotions/${edit.id}`, body);
      else await http.post("/admin/promotions", body);
      toast.success("Saved"); setEdit(null); load();
    } catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
  };
  const del = async (id) => { if(!window.confirm("Delete promo?")) return; await http.delete(`/admin/promotions/${id}`); load(); };
  return (
    <div>
      <PageHeader overline="Growth" title="Promotions" desc="Bonus credits and discount codes that resellers and clients can apply at top-up."
        actions={<Btn onClick={()=>setEdit({...empty})} data-testid="add-promo"><Plus className="h-4 w-4"/>New promo</Btn>}/>
      <Table testid="promos-table" rows={items} columns={[
        { key:"name", label:"Name" },
        { key:"code", label:"Code", mono:true, render: r => <code className="font-mono text-white">{r.code}</code> },
        { key:"type", label:"Type", mono:true },
        { key:"value", label:"Value", mono:true, render: r => r.type==="percent_discount"?`${r.value}%`:`$${r.value}` },
        { key:"min_topup", label:"Min top-up", mono:true, render: r => `$${r.min_topup}` },
        { key:"active", label:"Status", render: r => <Pill status={r.active?"active":"down"}/> },
        { key:"actions", label:"", render: r => (
          <div className="flex gap-2">
            <button onClick={()=>setEdit({...r})} className="text-zinc-400 hover:text-white text-xs underline">edit</button>
            <button onClick={()=>del(r.id)} className="text-zinc-400 hover:text-red-400"><Trash2 className="h-4 w-4"/></button>
          </div>
        )},
      ]}/>
      <Modal open={!!edit} onClose={()=>setEdit(null)} title={edit?.id?"Edit promotion":"New promotion"}>
        {edit && (
          <form onSubmit={save} className="grid gap-3 sm:grid-cols-2">
            <Field label="Name"><Input value={edit.name} onChange={(e)=>setEdit({...edit,name:e.target.value})} required/></Field>
            <Field label="Code"><Input value={edit.code} onChange={(e)=>setEdit({...edit,code:e.target.value.toUpperCase()})} required/></Field>
            <Field label="Type">
              <Select value={edit.type} onChange={(e)=>setEdit({...edit,type:e.target.value})}>
                <option value="bonus_credit">bonus credit ($)</option>
                <option value="percent_discount">percent bonus (%)</option>
                <option value="free_sms">free SMS pack</option>
              </Select>
            </Field>
            <Field label="Value"><Input type="number" step="any" value={edit.value} onChange={(e)=>setEdit({...edit,value:e.target.value})}/></Field>
            <Field label="Min top-up (USD)"><Input type="number" step="any" value={edit.min_topup} onChange={(e)=>setEdit({...edit,min_topup:e.target.value})}/></Field>
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
