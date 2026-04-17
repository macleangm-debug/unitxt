import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr, shortDate } from "@/lib/api";
import { PageHeader, Card, Field, Input, Btn, Table } from "@/components/UI";
import { Plus, Trash2, Copy } from "lucide-react";

export default function ApiKeys() {
  const [items, setItems] = useState([]);
  const [name, setName] = useState("");
  const load = () => http.get("/api-keys").then(r => setItems(r.data)).catch(()=>{});
  useEffect(load, []);
  const create = async (e) => {
    e.preventDefault();
    try { await http.post("/api-keys", { name }); setName(""); load(); toast.success("API key created"); }
    catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
  };
  const del = async (id) => { await http.delete(`/api-keys/${id}`); load(); };
  const copy = (k) => { navigator.clipboard.writeText(k); toast.success("Copied"); };
  return (
    <div>
      <PageHeader overline="Programmatic access" title="API keys" desc="Use keys to send messages from your application or CRM."/>
      <Card>
        <form onSubmit={create} className="grid gap-3 sm:grid-cols-[1fr,auto]">
          <Field label="Key name"><Input value={name} onChange={(e)=>setName(e.target.value)} placeholder="Production CRM" required data-testid="key-name"/></Field>
          <div className="flex items-end"><Btn type="submit" data-testid="key-create"><Plus className="h-4 w-4"/>Create</Btn></div>
        </form>
      </Card>
      <h2 className="label-overline mt-8 mb-3">Existing keys</h2>
      <Table testid="keys-table" rows={items} empty="No API keys yet." columns={[
        { key:"name", label:"Name" },
        { key:"key", label:"Key", render: r => <code className="font-mono text-xs text-zinc-300">{r.key}</code> },
        { key:"created_at", label:"Created", mono:true, render: r => shortDate(r.created_at) },
        { key:"actions", label:"", render: r => (
          <div className="flex gap-2">
            <button onClick={()=>copy(r.key)} className="text-zinc-500 hover:text-white" data-testid={`copy-${r.id}`}><Copy className="h-4 w-4"/></button>
            <button onClick={()=>del(r.id)} className="text-zinc-500 hover:text-red-400" data-testid={`del-key-${r.id}`}><Trash2 className="h-4 w-4"/></button>
          </div>
        )},
      ]}/>
    </div>
  );
}
