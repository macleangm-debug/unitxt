import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr } from "@/lib/api";
import { PageHeader, Card, Field, Input, Select, Btn, Table, Pill, Modal } from "@/components/UI";
import { Plus } from "lucide-react";

const empty = { code:"", name:"", currency:"USD", dial_code:"+1", sender_id_required:true, active:true };

export default function AdminCountries() {
  const [items, setItems] = useState([]);
  const [edit, setEdit] = useState(null);
  const load = () => http.get("/admin/countries").then(r => setItems(r.data)).catch(()=>{});
  useEffect(() => { load(); }, []);

  const save = async (e) => {
    e.preventDefault();
    try {
      if (edit.id) await http.patch(`/admin/countries/${edit.id}`, { active: edit.active, currency: edit.currency, name: edit.name });
      else await http.post("/admin/countries", edit);
      toast.success("Saved"); setEdit(null); load();
    } catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
  };

  return (
    <div>
      <PageHeader overline="Geography" title="Countries" desc="Activate or onboard countries. Each one can have its own provider, pricing, and rules."
        actions={<Btn onClick={()=>setEdit({...empty})} data-testid="add-country"><Plus className="h-4 w-4"/>Add country</Btn>}/>
      <Table testid="countries-table" rows={items} columns={[
        { key:"code", label:"Code", mono:true },
        { key:"name", label:"Name" },
        { key:"currency", label:"Currency", mono:true },
        { key:"dial_code", label:"Dial code", mono:true },
        { key:"active", label:"Status", render: r => <Pill status={r.active?"active":"down"}/> },
        { key:"actions", label:"", render: r => <button onClick={()=>setEdit({...r})} className="text-zinc-400 hover:text-white text-xs underline">edit</button> },
      ]}/>
      <Modal open={!!edit} onClose={()=>setEdit(null)} title={edit?.id?"Edit country":"Add country"}>
        {edit && (
          <form onSubmit={save} className="grid gap-3 sm:grid-cols-2">
            <Field label="Code (ISO 2)"><Input value={edit.code} onChange={(e)=>setEdit({...edit,code:e.target.value.toUpperCase()})} maxLength={2} required disabled={!!edit.id}/></Field>
            <Field label="Name"><Input value={edit.name} onChange={(e)=>setEdit({...edit,name:e.target.value})} required/></Field>
            <Field label="Currency"><Input value={edit.currency} onChange={(e)=>setEdit({...edit,currency:e.target.value.toUpperCase()})}/></Field>
            <Field label="Dial code"><Input value={edit.dial_code} onChange={(e)=>setEdit({...edit,dial_code:e.target.value})}/></Field>
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
