import { useEffect, useState } from "react";
import * as XLSX from "xlsx";
import { toast } from "sonner";
import http, { fmtErr } from "@/lib/api";
import {
  PageHeader, Card, Field, Input, TextArea, Btn, Table, Pill, Stat, Select,
} from "@/components/UI";
import {
  CheckCircle2, AlertCircle, Upload, Globe2, Gauge, Info, Zap,
} from "lucide-react";

export default function NumberLookup() {
  const [svc, setSvc] = useState(null);
  const [service, setService] = useState("smart_validation");
  const [single, setSingle] = useState("");
  const [singleResult, setSingleResult] = useState(null);

  const [bulkText, setBulkText] = useState("");
  const [bulkLoading, setBulkLoading] = useState(false);
  const [bulkResult, setBulkResult] = useState(null);

  useEffect(() => {
    http.get("/numbers/services").then(r => setSvc(r.data)).catch(() => {});
  }, []);

  const chosen = svc?.[service];
  const hlrLive = svc?.hlr_lookup?.status === "live";

  const runSingle = async (e) => {
    e.preventDefault();
    if (!single.trim()) return;
    try {
      const r = await http.post("/numbers/validate", { phone: single.trim(), service });
      setSingleResult(r.data);
      toast.success(`Used ${r.data.credits_charged} credit${r.data.credits_charged === 1 ? "" : "s"}`);
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
  };

  const runBulkText = async () => {
    const phones = bulkText.split(/\n|,|;/).map(s => s.trim()).filter(Boolean);
    if (!phones.length) return toast.error("Paste at least one number");
    return runBulk(phones);
  };

  const runBulkFile = async (file) => {
    const data = await file.arrayBuffer();
    const wb = XLSX.read(data);
    const ws = wb.Sheets[wb.SheetNames[0]];
    const rows = XLSX.utils.sheet_to_json(ws, { header: 1 });
    const phones = rows.flat().map(v => String(v || "").trim())
      .filter(v => v && /^\+?[0-9\s\-()]{6,}$/.test(v));
    if (!phones.length) return toast.error("No valid-looking numbers found in the file.");
    return runBulk(phones);
  };

  const runBulk = async (phones) => {
    setBulkLoading(true); setBulkResult(null);
    try {
      const r = await http.post("/numbers/validate/bulk", { phones, service });
      setBulkResult(r.data);
      toast.success(`${r.data.valid} valid of ${r.data.total} — charged ${r.data.credits_charged} credits`);
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
    finally { setBulkLoading(false); }
  };

  const exportResults = () => {
    if (!bulkResult?.results?.length) return;
    const ws = XLSX.utils.json_to_sheet(bulkResult.results.map(r => ({
      phone: r.input, formatted: r.e164, valid: r.valid ? "Yes" : "No",
      country: r.country || "", operator: r.operator || "",
      last_delivery: r.last_delivery_status || "",
      success_rate: r.historical_success_rate ?? "",
      risk: (r.risk_flags || []).join("; "),
    })));
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Validation");
    XLSX.writeFile(wb, `number-lookup-${Date.now()}.xlsx`);
  };

  return (
    <div>
      <PageHeader
        overline="Data hygiene"
        title="Number lookup"
        desc="Keep your contact list clean. Check numbers one at a time or upload a spreadsheet."
      />

      {/* Service picker */}
      {svc && (
        <div className="mb-4 grid gap-3 md:grid-cols-2" data-testid="service-picker">
          <ServiceCard
            k="smart_validation"
            active={service === "smart_validation"}
            onClick={() => setService("smart_validation")}
            icon={Zap}
            title={svc.smart_validation.name}
            description={svc.smart_validation.description}
            cost={svc.smart_validation.cost_per_lookup}
            badge="Available everywhere"
          />
          <ServiceCard
            k="hlr_lookup"
            active={service === "hlr_lookup"}
            onClick={() => hlrLive && setService("hlr_lookup")}
            disabled={!hlrLive}
            icon={Globe2}
            title={svc.hlr_lookup.name}
            description={svc.hlr_lookup.description}
            cost={svc.hlr_lookup.cost_per_lookup}
            badge={hlrLive
              ? `Live in ${(svc.hlr_lookup.available_countries || []).join(", ") || "—"}`
              : "Coming soon — we're onboarding operators"}
          />
        </div>
      )}

      {chosen && !hlrLive && service === "hlr_lookup" && (
        <div className="mb-4 flex items-start gap-2 border border-amber-500/30 bg-amber-500/5 p-3 text-xs text-amber-200">
          <Info className="mt-0.5 h-4 w-4 shrink-0"/>
          HLR lookup is not live yet. Use Smart number validation — it covers the most common checks without needing telco integration.
        </div>
      )}

      {/* Single lookup */}
      <Card testid="single-lookup" className="mb-4">
        <div className="label-overline mb-3">Check a single number</div>
        <form onSubmit={runSingle} className="flex flex-wrap items-end gap-3">
          <Field label="Phone number" hint={`Costs ${chosen?.cost_per_lookup || 1} credit per lookup.`}>
            <Input value={single} onChange={(e) => setSingle(e.target.value)}
                   placeholder="+255712345678" data-testid="single-phone"/>
          </Field>
          <Btn type="submit" data-testid="single-submit">Validate</Btn>
        </form>

        {singleResult && <SingleResult r={singleResult}/>}
      </Card>

      {/* Bulk lookup */}
      <Card testid="bulk-lookup">
        <div className="label-overline mb-3">Check a list</div>
        <div className="grid gap-3 md:grid-cols-2">
          <Field label="Paste numbers" hint="One per line, or separate by comma / semicolon.">
            <TextArea value={bulkText}
                       onChange={(e) => setBulkText(e.target.value)}
                       placeholder="+255712345678&#10;+254712345678"
                       className="min-h-[140px] font-mono text-xs"
                       data-testid="bulk-text"/>
          </Field>
          <div>
            <Field label="Or upload a spreadsheet" hint="Excel or CSV. We'll scan every cell for a phone-looking value.">
              <label className="block cursor-pointer border border-dashed border-zinc-800 bg-[#0e0e0e] p-6 text-center text-sm text-zinc-400 hover:border-zinc-600 hover:text-white">
                <Upload className="mx-auto mb-2 h-6 w-6" strokeWidth={1.5}/>
                Click to choose .xlsx, .xls or .csv
                <input type="file" accept=".xlsx,.xls,.csv"
                        onChange={(e) => e.target.files?.[0] && runBulkFile(e.target.files[0])}
                        className="hidden" data-testid="bulk-file"/>
              </label>
            </Field>
          </div>
        </div>
        <div className="mt-3 flex items-center justify-between">
          <span className="text-xs text-zinc-500">
            Up to 5,000 numbers per batch · Costs {chosen?.cost_per_lookup || 1} credits per number.
          </span>
          <Btn onClick={runBulkText} disabled={bulkLoading || !bulkText.trim()}
               data-testid="bulk-submit">
            {bulkLoading ? "Checking..." : "Validate pasted list"}
          </Btn>
        </div>

        {bulkResult && (
          <div className="mt-5" data-testid="bulk-results">
            <div className="mb-3 grid grid-cols-2 gap-0 border border-zinc-900 sm:grid-cols-4">
              <Stat label="Total" value={bulkResult.total}/>
              <Stat label="Valid" value={bulkResult.valid} accent="green"/>
              <Stat label="Invalid" value={bulkResult.invalid} accent={bulkResult.invalid > 0 ? "orange" : "white"}/>
              <Stat label="Credits used" value={bulkResult.credits_charged}/>
            </div>
            <div className="mb-3 flex justify-end">
              <Btn variant="ghost" onClick={exportResults} data-testid="bulk-export">Download results</Btn>
            </div>
            <Table
              testid="bulk-results-table"
              rows={bulkResult.results.slice(0, 200)}
              empty="No results."
              columns={[
                { key: "input", label: "Input", mono: true },
                { key: "e164", label: "Formatted", mono: true },
                { key: "country", label: "Country", mono: true },
                { key: "operator", label: "Operator" },
                { key: "valid", label: "Status",
                  render: r => r.valid ? <Pill status="active">Valid</Pill> : <Pill status="down">Invalid</Pill> },
                { key: "risk", label: "Notes",
                  render: r => (r.risk_flags || []).join("; ") || "—" },
              ]}
            />
          </div>
        )}
      </Card>
    </div>
  );
}

function ServiceCard({ active, onClick, disabled, icon: Icon, title, description, cost, badge }) {
  return (
    <button onClick={onClick} disabled={disabled} data-testid={`svc-${active ? "active" : "idle"}`}
            className={`text-left border p-5 transition ${
              active ? "border-white bg-white/5"
                     : disabled ? "border-zinc-900 bg-[#0e0e0e] opacity-60"
                                : "border-zinc-900 bg-[#141414] hover:border-zinc-700"
            }`}>
      <div className="flex items-start gap-3">
        <Icon className="mt-0.5 h-5 w-5 text-white" strokeWidth={1.5}/>
        <div className="flex-1">
          <div className="flex items-center gap-2">
            <span className="font-medium text-white">{title}</span>
            {active && <Pill status="active">selected</Pill>}
          </div>
          <p className="mt-1 text-xs text-zinc-400">{description}</p>
          <div className="mt-3 flex items-center gap-3 text-xs">
            <span className="text-zinc-500">Cost per lookup</span>
            <span className="font-mono text-white">{cost} credits</span>
          </div>
          <div className="mt-1 text-xs text-zinc-500">{badge}</div>
        </div>
      </div>
    </button>
  );
}

function SingleResult({ r }) {
  const ok = r.valid;
  return (
    <div className="mt-5 border border-zinc-900 bg-[#0e0e0e] p-4" data-testid="single-result">
      <div className="mb-3 flex items-center gap-2">
        {ok ? <CheckCircle2 className="h-5 w-5 text-emerald-400"/>
             : <AlertCircle className="h-5 w-5 text-red-400"/>}
        <span className="font-display text-lg font-semibold text-white">
          {ok ? "Looks good" : "Looks invalid"}
        </span>
        <Pill status={ok ? "active" : "down"}>{ok ? "valid" : "invalid"}</Pill>
      </div>
      <div className="grid gap-2 text-sm sm:grid-cols-2">
        <Row label="Formatted"    value={r.e164}/>
        <Row label="Country"      value={r.country || "—"}/>
        <Row label="Dial code"    value={r.dial_code || "—"}/>
        <Row label="Operator"     value={r.operator || "—"}/>
        <Row label="Mobile?"      value={r.is_mobile === true ? "Yes" : r.is_mobile === false ? "No" : "—"}/>
        <Row label="Seen before"  value={r.first_seen ? new Date(r.first_seen).toLocaleDateString() : "Never"}/>
        <Row label="Last delivery" value={r.last_delivery_status || "—"}/>
        <Row label="Past success rate"
             value={r.historical_success_rate != null ? `${Math.round(r.historical_success_rate * 100)}%` : "—"}/>
      </div>
      {r.risk_flags?.length > 0 && (
        <div className="mt-3 flex items-start gap-2 border border-amber-500/30 bg-amber-500/5 p-2 text-xs text-amber-200">
          <Info className="mt-0.5 h-3.5 w-3.5 shrink-0"/>
          <div>
            {r.risk_flags.map((f, i) => <div key={i}>{f}</div>)}
          </div>
        </div>
      )}
      <div className="mt-3 text-[11px] text-zinc-500">
        Charged <span className="font-mono text-zinc-300">{r.credits_charged}</span> credit{r.credits_charged === 1 ? "" : "s"}.
        {r.service === "smart_validation" && " · Smart validation does not confirm the handset is powered on right now — use HLR when available for live checks."}
      </div>
    </div>
  );
}

function Row({ label, value }) {
  return (
    <div className="flex justify-between border-b border-zinc-900 py-1">
      <span className="text-zinc-500">{label}</span>
      <span className="font-mono text-white">{value}</span>
    </div>
  );
}
