import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr } from "@/lib/api";
import { PageHeader, Field, Input, Select, Btn, Table, Pill, Modal } from "@/components/UI";
import { Plus, Trash2 } from "lucide-react";

const empty = { name:"", country:"TZ", channel:"sms", base_price:0.01, reseller_price:0.015, client_price:0.02, min_volume:0, active:true };

export default function AdminPricing() {
  const [items, setItems] = useState([]);
  const [edit, setEdit] = useState(null);
  const load = () => http.get("/admin/pricing").then(r => setItems(r.data)).catch(()=>{});
  useEffect(() => { load(); }, []);
  const save = async (e) => {
    e.preventDefault();
    const body = { ...edit,
      base_price:Number(edit.base_price), reseller_price:Number(edit.reseller_price),
      client_price:Number(edit.client_price), min_volume:Number(edit.min_volume) };
    try {
      if (edit.id) await http.patch(`/admin/pricing/${edit.id}`, body);
      else await http.post("/admin/pricing", body);
      toast.success("Saved"); setEdit(null); load();
    } catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
  };
  const del = async (id) => { if(!window.confirm("Delete plan?")) return; await http.delete(`/admin/pricing/${id}`); load(); };

  return (
    <div>
      <PageHeader overline="Money" title="Pricing" desc="Per-country, per-channel rates. Reseller buy price + client sell price."
        actions={<Btn onClick={()=>setEdit({...empty})} data-testid="add-pricing"><Plus className="h-4 w-4"/>Add plan</Btn>}/>
      <Table testid="pricing-table" rows={items} columns={[
        { key:"name", label:"Plan" },
        { key:"country", label:"Country", mono:true },
        { key:"channel", label:"Channel", mono:true },
        { key:"base_price", label:"Base", mono:true, render: r => `$${Number(r.base_price).toFixed(4)}` },
        { key:"reseller_price", label:"Reseller", mono:true, render: r => `$${Number(r.reseller_price||0).toFixed(4)}` },
        { key:"client_price", label:"Client", mono:true, render: r => `$${Number(r.client_price||0).toFixed(4)}` },
        { key:"active", label:"Status", render: r => <Pill status={r.active?"active":"down"}/> },
        { key:"actions", label:"", render: r => (
          <div className="flex gap-2">
            <button onClick={()=>setEdit({...r})} className="text-zinc-400 hover:text-white text-xs underline">edit</button>
            <button onClick={()=>del(r.id)} className="text-zinc-400 hover:text-red-400"><Trash2 className="h-4 w-4"/></button>
          </div>
        )},
      ]}/>
      <Modal open={!!edit} onClose={()=>setEdit(null)} title={edit?.id?"Edit pricing":"Add pricing"}>
        {edit && (
          <form onSubmit={save} className="grid gap-3 sm:grid-cols-2">
            <Field label="Plan name"><Input value={edit.name} onChange={(e)=>setEdit({...edit,name:e.target.value})} required/></Field>
            <Field label="Country (ISO)"><Input value={edit.country} onChange={(e)=>setEdit({...edit,country:e.target.value.toUpperCase()})} required/></Field>
            <Field label="Channel">
              <Select value={edit.channel} onChange={(e)=>setEdit({...edit,channel:e.target.value})}><option>sms</option><option>whatsapp</option></Select>
            </Field>
            <Field label="Min volume"><Input type="number" value={edit.min_volume} onChange={(e)=>setEdit({...edit,min_volume:e.target.value})}/></Field>
            <Field label="Base price"><Input type="number" step="any" value={edit.base_price} onChange={(e)=>setEdit({...edit,base_price:e.target.value})}/></Field>
            <Field label="Reseller price"><Input type="number" step="any" value={edit.reseller_price} onChange={(e)=>setEdit({...edit,reseller_price:e.target.value})}/></Field>
            <Field label="Client price"><Input type="number" step="any" value={edit.client_price} onChange={(e)=>setEdit({...edit,client_price:e.target.value})}/></Field>
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
