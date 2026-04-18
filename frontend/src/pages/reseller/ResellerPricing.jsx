import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr } from "@/lib/api";
import { PageHeader, Card, Field, Input, Select, Btn, Table, Pill } from "@/components/UI";
import { Plus, Trash2 } from "lucide-react";

const COUNTRIES = ["*","TZ","KE","UG","ZM","GH","NG","ZA","RW","US","GB","IN","AE"];

export default function ResellerPricing() {
  const [items, setItems] = useState([]);
  const [form, setForm] = useState({ country:"TZ", channel:"sms", markup:1.5, active:true });
  const load = () => http.get("/reseller/pricing").then(r => setItems(r.data)).catch(()=>{});
  useEffect(() => { load(); }, []);
  const save = async (e) => {
    e.preventDefault();
    try { await http.post("/reseller/pricing", { ...form, markup: Number(form.markup) }); toast.success("Saved"); load(); }
    catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
  };
  const del = async (id) => { await http.delete(`/reseller/pricing/${id}`); load(); };
  return (
    <div>
      <PageHeader overline="Your business" title="Client pricing"
        desc="Set a markup multiplier applied to your clients' sends. Your margin is credited to your float on every send."/>
      <div className="grid gap-4 lg:grid-cols-[2fr,3fr]">
        <Card testid="add-markup">
          <div className="label-overline mb-3">New markup rule</div>
          <form onSubmit={save} className="grid gap-3">
            <Field label="Country" hint={`Use "*" for default across all countries.`}>
              <Select value={form.country} onChange={(e)=>setForm({...form,country:e.target.value})}>
                {COUNTRIES.map(c => <option key={c}>{c}</option>)}
              </Select>
            </Field>
            <Field label="Channel">
              <Select value={form.channel} onChange={(e)=>setForm({...form,channel:e.target.value})}>
                <option>sms</option><option>whatsapp</option>
              </Select>
            </Field>
            <Field label="Markup multiplier" hint="1.0 = platform rate, 1.5 = +50%, 2.0 = 2× platform rate">
              <Input type="number" step="0.01" min="1" max="10" value={form.markup} onChange={(e)=>setForm({...form,markup:e.target.value})} data-testid="markup-input"/>
            </Field>
            <Btn type="submit" data-testid="markup-save"><Plus className="h-4 w-4"/>Save rule</Btn>
          </form>
        </Card>

        <Table testid="markup-table" rows={items} empty="No markup rules yet — clients pay the platform rate." columns={[
          { key:"country", label:"Country", mono:true },
          { key:"channel", label:"Channel", mono:true },
          { key:"markup", label:"Markup", mono:true, render: r => `${Number(r.markup).toFixed(2)}×` },
          { key:"active", label:"Status", render: r => <Pill status={r.active?"active":"down"}/> },
          { key:"actions", label:"", render: r => <button onClick={()=>del(r.id)} className="text-zinc-400 hover:text-red-400"><Trash2 className="h-4 w-4"/></button> },
        ]}/>
      </div>
    </div>
  );
}
