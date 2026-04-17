import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr, shortDate } from "@/lib/api";
import { PageHeader, Card, Field, Input, TextArea, Select, Btn, Table, Pill, Modal } from "@/components/UI";
import { Plus } from "lucide-react";

const COUNTRIES = [["TZ","Tanzania"],["KE","Kenya"],["UG","Uganda"],["ZM","Zambia"],["GH","Ghana"],["NG","Nigeria"],["ZA","South Africa"],["RW","Rwanda"],["US","US"],["GB","UK"],["IN","India"],["AE","UAE"]];

export default function SenderIds() {
  const [items, setItems] = useState([]);
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({ sender_id: "", country: "TZ", use_case: "", sample_message: "" });

  const load = () => http.get("/sender-ids").then(r => setItems(r.data)).catch(()=>{});
  useEffect(load, []);

  const submit = async (e) => {
    e.preventDefault();
    try { await http.post("/sender-ids", form); toast.success("Submitted for review"); setOpen(false); setForm({sender_id:"",country:"TZ",use_case:"",sample_message:""}); load(); }
    catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
  };

  return (
    <div>
      <PageHeader overline="Brand identity" title="Sender IDs" desc="Request, track, and manage the names that appear on outgoing messages."
        actions={<Btn onClick={()=>setOpen(true)} data-testid="request-sid-btn"><Plus className="h-4 w-4"/>Request sender ID</Btn>}/>

      <Table testid="sids-table" rows={items} empty="No sender IDs yet. Request your first." columns={[
        { key: "sender_id", label: "Sender ID", mono: true, render: r => <span className="font-mono text-white">{r.sender_id}</span> },
        { key: "country", label: "Country", mono: true },
        { key: "use_case", label: "Use case" },
        { key: "status", label: "Status", render: r => <Pill status={r.status}/> },
        { key: "created_at", label: "Submitted", mono: true, render: r => shortDate(r.created_at) },
      ]}/>

      <Modal open={open} onClose={()=>setOpen(false)} title="Request sender ID" testid="sid-modal">
        <form onSubmit={submit} className="space-y-3">
          <Field label="Sender ID" hint="Max 11 chars, alphanumeric only"><Input value={form.sender_id} onChange={(e)=>setForm({...form,sender_id:e.target.value})} required maxLength={11} data-testid="sid-name"/></Field>
          <Field label="Country">
            <Select value={form.country} onChange={(e)=>setForm({...form,country:e.target.value})} data-testid="sid-country">
              {COUNTRIES.map(([c,n])=> <option key={c} value={c}>{n}</option>)}
            </Select>
          </Field>
          <Field label="Use case"><Input value={form.use_case} onChange={(e)=>setForm({...form,use_case:e.target.value})} required placeholder="Bank OTP, marketing, alerts…" data-testid="sid-usecase"/></Field>
          <Field label="Sample message"><TextArea value={form.sample_message} onChange={(e)=>setForm({...form,sample_message:e.target.value})} required data-testid="sid-sample"/></Field>
          <Btn type="submit" className="w-full" data-testid="sid-submit">Submit for review</Btn>
        </form>
      </Modal>
    </div>
  );
}
