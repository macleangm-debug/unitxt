import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr } from "@/lib/api";
import { PageHeader, Card, Field, Input, Select, Btn, Table, Pill, Modal } from "@/components/UI";
import { Plus, Edit, Trash2, Cable, Globe2, Info } from "lucide-react";

const empty = {
  name: "", type: "aggregator", countries: "*", channels: "sms",
  // HTTP
  api_key: "", api_secret: "", base_url: "",
  // Common
  cost_per_sms: 0.02, buy_price_local_pre_vat: 0, priority: 5,
  active: true, supports_unicode: true, supports_dlr: true,
  // SMPP
  transport: "http",
  smpp_host: "", smpp_port: 2775, smpp_system_id: "", smpp_password: "",
  smpp_system_type: "", smpp_bind_mode: "trx", smpp_use_tls: false,
  smpp_source_ton: 5, smpp_source_npi: 0,
  smpp_dest_ton: 1, smpp_dest_npi: 1,
  smpp_throughput_per_sec: 30, smpp_window_size: 10, smpp_notes: "",
};

export default function AdminProviders() {
  const [items, setItems] = useState([]);
  const [edit, setEdit] = useState(null);
  const load = () => http.get("/admin/providers").then(r => setItems(r.data)).catch(()=>{});
  useEffect(() => { load(); }, []);

  const save = async (e) => {
    e.preventDefault();
    const numericKeys = ["cost_per_sms", "buy_price_local_pre_vat", "priority",
                         "smpp_port", "smpp_source_ton", "smpp_source_npi",
                         "smpp_dest_ton", "smpp_dest_npi",
                         "smpp_throughput_per_sec", "smpp_window_size"];
    const body = {
      ...edit,
      countries: typeof edit.countries === "string" ? edit.countries.split(",").map(s=>s.trim()).filter(Boolean) : edit.countries,
      channels:  typeof edit.channels  === "string" ? edit.channels.split(",").map(s=>s.trim()).filter(Boolean)  : edit.channels,
    };
    numericKeys.forEach(k => { if (body[k] !== "" && body[k] != null) body[k] = Number(body[k]); });
    try {
      if (edit.id) await http.patch(`/admin/providers/${edit.id}`, body);
      else await http.post("/admin/providers", body);
      toast.success("Saved"); setEdit(null); load();
    } catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
  };
  const del = async (id) => { if(!window.confirm("Delete provider?")) return; await http.delete(`/admin/providers/${id}`); load(); };

  const isSmpp = edit?.transport === "smpp";

  return (
    <div>
      <PageHeader overline="Carriers" title="Providers" desc="Plug in telco APIs, aggregators, and direct SMPP carriers."
        actions={<Btn onClick={()=>setEdit({...empty})} data-testid="add-provider"><Plus className="h-4 w-4"/>Add provider</Btn>}/>
      <Table testid="providers-table" rows={items} columns={[
        { key:"name", label:"Name", render: r => (
          <div>
            <div className="font-medium">{r.name}</div>
            <div className="font-mono text-[10px] uppercase tracking-widest text-zinc-500">{r.type}</div>
          </div>
        )},
        { key:"transport", label:"Transport", render: r => (
          <Pill status={r.transport === "smpp" ? "info" : "active"}>
            {(r.transport || "http").toUpperCase()}
          </Pill>
        )},
        { key:"countries", label:"Countries", mono:true, render: r => (r.countries||[]).join(", ") || "*" },
        { key:"channels", label:"Channels", mono:true, render: r => (r.channels||[]).join(", ") },
        { key:"priority", label:"Priority", mono:true },
        { key:"cost_per_sms", label:"Cost/SMS", mono:true, render: r => `$${Number(r.cost_per_sms).toFixed(4)}` },
        { key:"smpp_status", label:"Bind", render: r => (
          r.transport === "smpp"
            ? <Pill status={r.smpp_bind_status === "bound" ? "active" : "down"}>
                {r.smpp_bind_status || "unknown"}
              </Pill>
            : <span className="text-zinc-600">—</span>
        )},
        { key:"active", label:"Status", render: r => <Pill status={r.active?"active":"down"}/> },
        { key:"actions", label:"", render: r => (
          <div className="flex gap-2">
            <button onClick={()=>setEdit({...r, countries:(r.countries||[]).join(","), channels:(r.channels||[]).join(",")})} className="text-zinc-400 hover:text-white" data-testid={`edit-${r.id}`}><Edit className="h-4 w-4"/></button>
            <button onClick={()=>del(r.id)} className="text-zinc-400 hover:text-red-400" data-testid={`del-${r.id}`}><Trash2 className="h-4 w-4"/></button>
          </div>
        )},
      ]}/>

      <Modal open={!!edit} onClose={()=>setEdit(null)} title={edit?.id?"Edit provider":"Add provider"}>
        {edit && (
          <form onSubmit={save} className="grid gap-3 sm:grid-cols-2" data-testid="provider-form">
            <Field label="Name"><Input value={edit.name} onChange={(e)=>setEdit({...edit,name:e.target.value})} required data-testid="prov-name"/></Field>
            <Field label="Type">
              <Select value={edit.type} onChange={(e)=>setEdit({...edit,type:e.target.value})}>
                <option>aggregator</option><option>direct_telco</option><option>api_partner</option>
              </Select>
            </Field>
            <Field label="Countries (csv or *)"><Input value={edit.countries} onChange={(e)=>setEdit({...edit,countries:e.target.value})}/></Field>
            <Field label="Channels (csv: sms,whatsapp)"><Input value={edit.channels} onChange={(e)=>setEdit({...edit,channels:e.target.value})}/></Field>

            {/* TRANSPORT SWITCH */}
            <Field label="Transport">
              <Select value={edit.transport || "http"} onChange={(e)=>setEdit({...edit,transport:e.target.value})} data-testid="prov-transport">
                <option value="http">HTTP / REST API</option>
                <option value="smpp">SMPP (direct telco bind)</option>
              </Select>
            </Field>
            <Field label="Cost / SMS (USD)"><Input type="number" step="any" value={edit.cost_per_sms} onChange={(e)=>setEdit({...edit,cost_per_sms:e.target.value})}/></Field>
            <Field label="Buy price pre-VAT (local)"><Input type="number" step="any" value={edit.buy_price_local_pre_vat ?? 0} onChange={(e)=>setEdit({...edit,buy_price_local_pre_vat:e.target.value})}/></Field>
            <Field label="Priority (lower = first)"><Input type="number" value={edit.priority} onChange={(e)=>setEdit({...edit,priority:e.target.value})}/></Field>
            <Field label="Active">
              <Select value={edit.active?"1":"0"} onChange={(e)=>setEdit({...edit,active:e.target.value==="1"})}>
                <option value="1">active</option><option value="0">inactive</option>
              </Select>
            </Field>

            {/* HTTP-specific block */}
            {!isSmpp && (
              <div className="sm:col-span-2 mt-2 grid gap-3 border border-zinc-900 bg-[#0e0e0e] p-3 sm:grid-cols-2">
                <div className="sm:col-span-2 flex items-center gap-2 text-xs text-zinc-400">
                  <Globe2 className="h-3.5 w-3.5"/> HTTP / REST credentials
                </div>
                <Field label="API key"><Input value={edit.api_key} onChange={(e)=>setEdit({...edit,api_key:e.target.value})} data-testid="prov-api-key"/></Field>
                <Field label="API secret"><Input value={edit.api_secret} onChange={(e)=>setEdit({...edit,api_secret:e.target.value})}/></Field>
                <div className="sm:col-span-2"><Field label="Base URL"><Input value={edit.base_url} onChange={(e)=>setEdit({...edit,base_url:e.target.value})}/></Field></div>
              </div>
            )}

            {/* SMPP-specific block */}
            {isSmpp && (
              <div className="sm:col-span-2 mt-2 grid gap-3 border border-blue-900/50 bg-blue-950/20 p-3 sm:grid-cols-2">
                <div className="sm:col-span-2 flex items-center gap-2 text-xs text-blue-300">
                  <Cable className="h-3.5 w-3.5"/> SMPP bind credentials
                </div>
                <div className="sm:col-span-2 flex items-start gap-2 border border-blue-900/40 bg-blue-950/40 p-2 text-[11px] text-blue-200">
                  <Info className="h-3.5 w-3.5 mt-0.5 shrink-0"/>
                  <span>
                    SMPP runs on a long-lived TCP bind. The unitxt SMPP relay daemon must be deployed on
                    the host with the IPSec VPN tunnel to the SMSC (only that source IP is whitelisted by
                    Tigo). See <code className="font-mono text-blue-300">backend/smpp_relay/README.md</code>.
                  </span>
                </div>
                <Field label="SMPP host"><Input value={edit.smpp_host || ""} onChange={(e)=>setEdit({...edit,smpp_host:e.target.value})} placeholder="smpp01.tigo.co.tz" data-testid="prov-smpp-host"/></Field>
                <Field label="SMPP port"><Input type="number" value={edit.smpp_port ?? 2775} onChange={(e)=>setEdit({...edit,smpp_port:e.target.value})} placeholder="10501"/></Field>
                <Field label="System ID (username)"><Input value={edit.smpp_system_id || ""} onChange={(e)=>setEdit({...edit,smpp_system_id:e.target.value})} data-testid="prov-smpp-sysid"/></Field>
                <Field label="Password"><Input type="password" value={edit.smpp_password || ""} onChange={(e)=>setEdit({...edit,smpp_password:e.target.value})}/></Field>
                <Field label="System type (optional)"><Input value={edit.smpp_system_type || ""} onChange={(e)=>setEdit({...edit,smpp_system_type:e.target.value})} placeholder="(blank for most carriers)"/></Field>
                <Field label="Bind mode">
                  <Select value={edit.smpp_bind_mode || "trx"} onChange={(e)=>setEdit({...edit,smpp_bind_mode:e.target.value})}>
                    <option value="trx">TRX (transmit + receive)</option>
                    <option value="tx">TX (transmit only)</option>
                    <option value="rx">RX (receive only)</option>
                  </Select>
                </Field>
                <Field label="Throughput / sec"><Input type="number" value={edit.smpp_throughput_per_sec ?? 30} onChange={(e)=>setEdit({...edit,smpp_throughput_per_sec:e.target.value})}/></Field>
                <Field label="Window size"><Input type="number" value={edit.smpp_window_size ?? 10} onChange={(e)=>setEdit({...edit,smpp_window_size:e.target.value})}/></Field>
                <Field label="Source TON (5=alphanumeric)"><Input type="number" value={edit.smpp_source_ton ?? 5} onChange={(e)=>setEdit({...edit,smpp_source_ton:e.target.value})}/></Field>
                <Field label="Source NPI"><Input type="number" value={edit.smpp_source_npi ?? 0} onChange={(e)=>setEdit({...edit,smpp_source_npi:e.target.value})}/></Field>
                <Field label="Dest TON (1=international)"><Input type="number" value={edit.smpp_dest_ton ?? 1} onChange={(e)=>setEdit({...edit,smpp_dest_ton:e.target.value})}/></Field>
                <Field label="Dest NPI (1=E.164)"><Input type="number" value={edit.smpp_dest_npi ?? 1} onChange={(e)=>setEdit({...edit,smpp_dest_npi:e.target.value})}/></Field>
                <Field label="Use TLS">
                  <Select value={edit.smpp_use_tls ? "1" : "0"} onChange={(e)=>setEdit({...edit,smpp_use_tls:e.target.value==="1"})}>
                    <option value="0">no</option><option value="1">yes</option>
                  </Select>
                </Field>
                <div className="sm:col-span-2"><Field label="Notes (admin-only)"><Input value={edit.smpp_notes || ""} onChange={(e)=>setEdit({...edit,smpp_notes:e.target.value})} placeholder="e.g. provisioned by Tigo on 2026-04-26 — sender IDs preregistered"/></Field></div>
              </div>
            )}

            <div className="sm:col-span-2"><Btn type="submit" className="w-full" data-testid="save-provider">Save</Btn></div>
          </form>
        )}
      </Modal>
    </div>
  );
}
