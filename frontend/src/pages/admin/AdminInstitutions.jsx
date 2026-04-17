import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr } from "@/lib/api";
import { PageHeader, Field, Input, Select, Btn, Table, Pill, Modal } from "@/components/UI";
import { Plus } from "lucide-react";

const empty = { name:"", type:"bank", country:"TZ", api_credentials:{}, callback_url:"", active:true };

export default function AdminInstitutions() {
  const [items, setItems] = useState([]);
  const [edit, setEdit] = useState(null);
  const load = () => http.get("/admin/institutions").then(r => setItems(r.data)).catch(()=>{});
  useEffect(load, []);
  const save = async (e) => {
    e.preventDefault();
    try {
      if (edit.id) await http.patch(`/admin/institutions/${edit.id}`, edit);
      else await http.post("/admin/institutions", edit);
      toast.success("Saved"); setEdit(null); load();
    } catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
  };
  return (
    <div>
      <PageHeader overline="Connections" title="Institutions" desc="Banks, fintechs, mobile money operators that can connect to unitxt."
        actions={<Btn onClick={()=>setEdit({...empty})} data-testid="add-inst"><Plus className="h-4 w-4"/>Add institution</Btn>}/>
      <Table testid="institutions-table" rows={items} columns={[
        { key:"name", label:"Name" },
        { key:"type", label:"Type", mono:true },
        { key:"country", label:"Country", mono:true },
        { key:"callback_url", label:"Callback", mono:true, render: r => r.callback_url || "—" },
        { key:"active", label:"Status", render: r => <Pill status={r.active?"active":"down"}/> },
        { key:"actions", label:"", render: r => <button onClick={()=>setEdit({...r})} className="text-zinc-400 hover:text-white text-xs underline">edit</button> },
      ]}/>
      <Modal open={!!edit} onClose={()=>setEdit(null)} title={edit?.id?"Edit institution":"Add institution"}>
        {edit && (
          <form onSubmit={save} className="grid gap-3 sm:grid-cols-2">
            <Field label="Name"><Input value={edit.name} onChange={(e)=>setEdit({...edit,name:e.target.value})} required/></Field>
            <Field label="Type">
              <Select value={edit.type} onChange={(e)=>setEdit({...edit,type:e.target.value})}>
                <option>bank</option><option>mobile_money</option><option>fintech</option><option>enterprise</option>
              </Select>
            </Field>
            <Field label="Country (ISO)"><Input value={edit.country} onChange={(e)=>setEdit({...edit,country:e.target.value.toUpperCase()})} required/></Field>
            <Field label="Callback URL"><Input value={edit.callback_url} onChange={(e)=>setEdit({...edit,callback_url:e.target.value})}/></Field>
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
