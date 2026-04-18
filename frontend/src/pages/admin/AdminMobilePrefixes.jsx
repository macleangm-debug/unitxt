import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr } from "@/lib/api";
import { PageHeader, Card, Field, Input, Select, Btn, Table, Pill, Modal } from "@/components/UI";
import { Plus, Trash2, Upload, Search } from "lucide-react";

const empty = { country:"TZ", operator:"", prefix:"+255", active:true };

export default function AdminMobilePrefixes() {
  const [items, setItems] = useState([]);
  const [filter, setFilter] = useState("");
  const [edit, setEdit] = useState(null);
  const [csv, setCsv] = useState("country,operator,prefix\nTZ,Smart,+25563\nKE,Faiba,+25474");
  const [importOpen, setImportOpen] = useState(false);
  const [lookup, setLookup] = useState("");
  const [lookupResult, setLookupResult] = useState(null);

  const load = () => http.get("/prefixes").then(r => setItems(r.data)).catch(()=>{});
  useEffect(() => { load(); }, []);

  const save = async (e) => {
    e.preventDefault();
    try {
      if (edit.id) await http.patch(`/prefixes/${edit.id}`, edit);
      else await http.post("/prefixes", edit);
      toast.success("Saved"); setEdit(null); load();
    } catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
  };
  const del = async (id) => { if(!window.confirm("Delete prefix?")) return; await http.delete(`/prefixes/${id}`); load(); };

  const doImport = async () => {
    const lines = csv.trim().split(/\n/);
    if (lines.length < 2) return toast.error("Need at least 2 lines (header + one row).");
    const headers = lines[0].split(",").map(s => s.trim());
    const rows = lines.slice(1).map(line => {
      const vals = line.split(",").map(s=>s.trim());
      const r = { active: true };
      headers.forEach((h, i) => { r[h] = vals[i] || ""; });
      return r;
    }).filter(r => r.country && r.operator && r.prefix);
    try {
      const { data } = await http.post("/prefixes/import", rows);
      toast.success(`Imported ${data.count} prefixes`);
      setImportOpen(false); load();
    } catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
  };

  const onFile = (e) => {
    const f = e.target.files?.[0];
    if (!f) return;
    const reader = new FileReader();
    reader.onload = () => setCsv(String(reader.result));
    reader.readAsText(f);
  };

  const doLookup = async () => {
    try {
      const { data } = await http.get(`/prefixes/lookup?phone=${encodeURIComponent(lookup)}`);
      setLookupResult(data.match);
    } catch(err){ toast.error(fmtErr(err.response?.data?.detail)); }
  };

  const filtered = items.filter(i => !filter
    || i.country.toLowerCase().includes(filter.toLowerCase())
    || i.operator.toLowerCase().includes(filter.toLowerCase())
    || i.prefix.includes(filter));

  return (
    <div>
      <PageHeader overline="Routing data" title="Mobile prefixes"
        desc="Per-operator prefixes per country. Drives operator detection and smart routing."
        actions={
          <>
            <Btn variant="secondary" onClick={()=>setImportOpen(true)} data-testid="import-prefixes-btn"><Upload className="h-4 w-4"/>Import CSV</Btn>
            <Btn onClick={()=>setEdit({...empty})} data-testid="add-prefix"><Plus className="h-4 w-4"/>Add prefix</Btn>
          </>
        }/>

      <div className="mb-4 grid gap-4 lg:grid-cols-2">
        <Card testid="prefix-lookup">
          <div className="label-overline">Lookup</div>
          <div className="mt-3 flex gap-2">
            <Input value={lookup} onChange={(e)=>setLookup(e.target.value)} placeholder="+255712345678" data-testid="lookup-phone"/>
            <Btn onClick={doLookup} data-testid="lookup-btn"><Search className="h-4 w-4"/>Detect</Btn>
          </div>
          {lookupResult && (
            <div className="mt-3 border border-zinc-800 bg-zinc-950 p-3 text-sm" data-testid="lookup-result">
              <div className="font-display text-lg font-semibold">{lookupResult.operator}</div>
              <div className="font-mono text-xs text-zinc-400">{lookupResult.country} · {lookupResult.prefix}</div>
            </div>
          )}
          {lookupResult === null && lookup && <div className="mt-3 text-sm text-orange-400">No match for {lookup}</div>}
        </Card>
        <Card testid="prefix-filter">
          <div className="label-overline">Filter</div>
          <Input className="mt-3" value={filter} onChange={(e)=>setFilter(e.target.value)} placeholder="Country, operator or prefix" data-testid="prefix-search"/>
          <div className="mt-3 font-mono text-xs text-zinc-500">{filtered.length} of {items.length} shown</div>
        </Card>
      </div>

      <Table testid="prefixes-table" rows={filtered} columns={[
        { key:"country", label:"Country", mono:true },
        { key:"operator", label:"Operator" },
        { key:"prefix", label:"Prefix", mono:true, render: r => <code className="font-mono text-white">{r.prefix}</code> },
        { key:"active", label:"Status", render: r => <Pill status={r.active?"active":"down"}/> },
        { key:"actions", label:"", render: r => (
          <div className="flex gap-2">
            <button onClick={()=>setEdit({...r})} className="text-zinc-400 hover:text-white text-xs underline">edit</button>
            <button onClick={()=>del(r.id)} className="text-zinc-400 hover:text-red-400"><Trash2 className="h-4 w-4"/></button>
          </div>
        )},
      ]}/>

      <Modal open={!!edit} onClose={()=>setEdit(null)} title={edit?.id?"Edit prefix":"Add prefix"}>
        {edit && (
          <form onSubmit={save} className="grid gap-3 sm:grid-cols-2">
            <Field label="Country (ISO)"><Input value={edit.country} onChange={(e)=>setEdit({...edit,country:e.target.value.toUpperCase()})} required/></Field>
            <Field label="Operator"><Input value={edit.operator} onChange={(e)=>setEdit({...edit,operator:e.target.value})} required/></Field>
            <Field label="Prefix"><Input value={edit.prefix} onChange={(e)=>setEdit({...edit,prefix:e.target.value})} required/></Field>
            <Field label="Active">
              <Select value={edit.active?"1":"0"} onChange={(e)=>setEdit({...edit,active:e.target.value==="1"})}>
                <option value="1">active</option><option value="0">inactive</option>
              </Select>
            </Field>
            <div className="sm:col-span-2"><Btn type="submit" className="w-full">Save</Btn></div>
          </form>
        )}
      </Modal>

      <Modal open={importOpen} onClose={()=>setImportOpen(false)} title="Import prefixes (CSV)">
        <div className="space-y-3">
          <label className="inline-flex items-center gap-2 cursor-pointer border border-zinc-800 bg-zinc-950 px-3 py-1.5 text-xs text-zinc-300 hover:border-zinc-600">
            <Upload className="h-3.5 w-3.5"/> Upload .csv
            <input type="file" accept=".csv,text/csv,text/plain" onChange={onFile} className="hidden" data-testid="import-file"/>
          </label>
          <div className="text-xs text-zinc-500">Columns (header row required): <code className="font-mono text-zinc-300">country,operator,prefix</code></div>
          <textarea value={csv} onChange={(e)=>setCsv(e.target.value)} className="min-h-[180px] w-full border border-zinc-800 bg-transparent p-3 font-mono text-xs text-white outline-none focus:border-white" data-testid="import-textarea"/>
          <Btn onClick={doImport} className="w-full" data-testid="import-submit">Import</Btn>
        </div>
      </Modal>
    </div>
  );
}
