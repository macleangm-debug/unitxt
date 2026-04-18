import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr, money, shortDate } from "@/lib/api";
import { PageHeader, Card, Field, Input, Select, Btn, Table, Pill, Modal } from "@/components/UI";
import { Edit, DollarSign } from "lucide-react";

export default function AdminUsers() {
  const [items, setItems] = useState([]);
  const [edit, setEdit] = useState(null);
  const [credit, setCredit] = useState(null);
  const [amount, setAmount] = useState(50);

  const load = () => http.get("/admin/users").then(r => setItems(r.data)).catch(()=>{});
  useEffect(() => { load(); }, []);

  const save = async (e) => {
    e.preventDefault();
    try {
      await http.patch(`/admin/users/${edit.id}`, { status: edit.status, role: edit.role, name: edit.name });
      toast.success("Updated"); setEdit(null); load();
    } catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
  };
  const doCredit = async (e) => {
    e.preventDefault();
    try { await http.post(`/admin/users/${credit.id}/credit`, { amount: Number(amount), method:"manual" }); toast.success("Credited"); setCredit(null); }
    catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
  };

  return (
    <div>
      <PageHeader overline="People" title="Users" desc="Suspend, role-change, or credit any account."/>
      <Table testid="users-table" rows={items} columns={[
        { key:"name", label:"Name", render: r => <div><div className="font-medium">{r.name}</div><div className="font-mono text-[10px] uppercase tracking-widest text-zinc-500">{r.email}</div></div> },
        { key:"role", label:"Role", render: r => <Pill status={r.role==="super_admin"?"info":r.role==="reseller"?"approved":"pending"}>{r.role.replace("_"," ")}</Pill> },
        { key:"country", label:"Country", mono:true },
        { key:"status", label:"Status", render: r => <Pill status={r.status}/> },
        { key:"created_at", label:"Joined", mono:true, render: r => shortDate(r.created_at) },
        { key:"actions", label:"", render: r => (
          <div className="flex gap-2">
            <button onClick={()=>setEdit({...r})} className="text-zinc-400 hover:text-white" data-testid={`edit-user-${r.id}`}><Edit className="h-4 w-4"/></button>
            <button onClick={()=>setCredit(r)} className="text-zinc-400 hover:text-emerald-400" data-testid={`credit-user-${r.id}`}><DollarSign className="h-4 w-4"/></button>
          </div>
        )},
      ]}/>

      <Modal open={!!edit} onClose={()=>setEdit(null)} title="Edit user">
        {edit && (
          <form onSubmit={save} className="space-y-3">
            <Field label="Name"><Input value={edit.name} onChange={(e)=>setEdit({...edit,name:e.target.value})}/></Field>
            <Field label="Role">
              <Select value={edit.role} onChange={(e)=>setEdit({...edit,role:e.target.value})}>
                {["client","reseller","staff","support","finance","compliance","country_admin","super_admin"].map(r=><option key={r} value={r}>{r}</option>)}
              </Select>
            </Field>
            <Field label="Status">
              <Select value={edit.status} onChange={(e)=>setEdit({...edit,status:e.target.value})}>
                <option value="active">active</option><option value="suspended">suspended</option>
              </Select>
            </Field>
            <Btn type="submit" className="w-full">Save</Btn>
          </form>
        )}
      </Modal>

      <Modal open={!!credit} onClose={()=>setCredit(null)} title={`Credit ${credit?.name||""}`}>
        {credit && (
          <form onSubmit={doCredit} className="space-y-3">
            <Field label="Credits to add"><Input type="number" value={amount} onChange={(e)=>setAmount(e.target.value)} min={0} step="1" required/></Field>
            <Btn type="submit" className="w-full">Credit account</Btn>
          </form>
        )}
      </Modal>
    </div>
  );
}
