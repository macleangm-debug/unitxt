import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr } from "@/lib/api";
import { PageHeader, Card, Field, Input, Select, Btn, Table, Pill, Modal } from "@/components/UI";
import { Plus, Edit, Trash2 } from "lucide-react";

const empty = { name:"", type:"aggregator", countries:"*", channels:"sms", api_key:"", api_secret:"", base_url:"", cost_per_sms:0.02, priority:5, active:true, supports_unicode:true, supports_dlr:true };

export default function AdminProviders() {
  const [items, setItems] = useState([]);
  const [edit, setEdit] = useState(null);
  const load = () => http.get("/admin/providers").then(r => setItems(r.data)).catch(()=>{});
  useEffect(() => { load(); }, []);

  const save = async (e) => {
    e.preventDefault();
    const body = {
      ...edit,
      countries: typeof edit.countries === "string" ? edit.countries.split(",").map(s=>s.trim()).filter(Boolean) : edit.countries,
      channels: typeof edit.channels === "string" ? edit.channels.split(",").map(s=>s.trim()).filter(Boolean) : edit.channels,
      cost_per_sms: Number(edit.cost_per_sms), priority: Number(edit.priority),
    };
    try {
      if (edit.id) await http.patch(`/admin/providers/${edit.id}`, body);
      else await http.post("/admin/providers", body);
      toast.success("Saved"); setEdit(null); load();
    } catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
  };
  const del = async (id) => { if(!window.confirm("Delete provider?")) return; await http.delete(`/admin/providers/${id}`); load(); };

  return (
    <div>
      <PageHeader overline="Carriers" title="Providers" desc="Plug in telco APIs, aggregators, and global partners."
        actions={<Btn onClick={()=>setEdit({...empty})} data-testid="add-provider"><Plus className="h-4 w-4"/>Add provider</Btn>}/>
      <Table testid="providers-table" rows={items} columns={[
        { key:"name", label:"Name", render: r => <div><div className="font-medium">{r.name}</div><div className="font-mono text-[10px] uppercase tracking-widest text-zinc-500">{r.type}</div></div> },
        { key:"countries", label:"Countries", mono:true, render: r => (r.countries||[]).join(", ") || "*" },
        { key:"channels", label:"Channels", mono:true, render: r => (r.channels||[]).join(", ") },
        { key:"priority", label:"Priority", mono:true },
        { key:"cost_per_sms", label:"Cost/SMS", mono:true, render: r => `$${Number(r.cost_per_sms).toFixed(4)}` },
        { key:"active", label:"Status", render: r => <Pill status={r.active?"active":"down"}/> },
        { key:"actions", label:"", render: r => (
          <div className="flex gap-2">
            <button onClick={()=>setEdit({...r, countries:(r.countries||[]).join(","), channels:(r.channels||[]).join(",")})} className="text-zinc-400 hover:text-white"><Edit className="h-4 w-4"/></button>
            <button onClick={()=>del(r.id)} className="text-zinc-400 hover:text-red-400"><Trash2 className="h-4 w-4"/></button>
          </div>
        )},
      ]}/>

      <Modal open={!!edit} onClose={()=>setEdit(null)} title={edit?.id?"Edit provider":"Add provider"}>
        {edit && (
          <form onSubmit={save} className="grid gap-3 sm:grid-cols-2">
            <Field label="Name"><Input value={edit.name} onChange={(e)=>setEdit({...edit,name:e.target.value})} required/></Field>
            <Field label="Type">
              <Select value={edit.type} onChange={(e)=>setEdit({...edit,type:e.target.value})}>
                <option>aggregator</option><option>direct_telco</option><option>api_partner</option>
              </Select>
            </Field>
            <Field label="Countries (csv or *)"><Input value={edit.countries} onChange={(e)=>setEdit({...edit,countries:e.target.value})}/></Field>
            <Field label="Channels (csv: sms,whatsapp)"><Input value={edit.channels} onChange={(e)=>setEdit({...edit,channels:e.target.value})}/></Field>
            <Field label="API key"><Input value={edit.api_key} onChange={(e)=>setEdit({...edit,api_key:e.target.value})}/></Field>
            <Field label="API secret"><Input value={edit.api_secret} onChange={(e)=>setEdit({...edit,api_secret:e.target.value})}/></Field>
            <Field label="Base URL"><Input value={edit.base_url} onChange={(e)=>setEdit({...edit,base_url:e.target.value})}/></Field>
            <Field label="Cost / SMS (USD)"><Input type="number" step="any" value={edit.cost_per_sms} onChange={(e)=>setEdit({...edit,cost_per_sms:e.target.value})}/></Field>
            <Field label="Priority (lower = first)"><Input type="number" value={edit.priority} onChange={(e)=>setEdit({...edit,priority:e.target.value})}/></Field>
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
