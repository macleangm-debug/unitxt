import { useEffect, useMemo, useState, useRef } from "react";
import * as XLSX from "xlsx";
import { toast } from "sonner";
import http, { fmtErr, creditsShort } from "@/lib/api";
import { useAuth } from "@/context/AuthContext";
import { PageHeader, Card, Field, Input, TextArea, Select, Btn, Pill, Modal } from "@/components/UI";
import { Upload, FileSpreadsheet, Tag, ListFilter } from "lucide-react";

export default function BulkSend() {
  const { user } = useAuth();
  const templateRef = useRef(null);
  const [name, setName] = useState("");
  const [channel, setChannel] = useState("sms");
  const [senderId, setSenderId] = useState("");
  const [csv, setCsv] = useState("phone,name,amount_owed\n+255712345678,Jane,15000\n+255700000001,John,8500");
  const [template, setTemplate] = useState("Hi {name}, your outstanding balance is {amount_owed}. Please settle by the due date. Thank you.");
  const [scheduleAt, setScheduleAt] = useState("");
  const [sids, setSids] = useState([]);
  const [groups, setGroups] = useState([]);
  const [pickedGroup, setPickedGroup] = useState("");
  const [rates, setRates] = useState(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    Promise.all([
      http.get("/sender-ids"),
      http.get("/credits/rates"),
      http.get("/contacts/groups"),
    ]).then(([s, r, g]) => {
      const approved = s.data.filter(x => x.status === "approved");
      setSids(approved);
      if (approved[0]) setSenderId(approved[0].sender_id);
      setRates(r.data);
      setGroups(g.data || []);
    }).catch(() => {});
  }, []);

  const rate = (!rates) ? 1
    : channel === "whatsapp" ? (rates.whatsapp_rate || 3)
    : (rates.country_rate?.[user?.country] || rates.default_rate || 2);

  const parseCsv = () => {
    const lines = csv.trim().split(/\n/);
    if (lines.length < 2) return { headers: [], rows: [] };
    const headers = lines[0].split(",").map(h => h.trim());
    const rows = lines.slice(1).map(line => {
      const vals = line.split(",").map(v => v.trim());
      const row = {};
      headers.forEach((h, i) => row[h] = vals[i] || "");
      return row;
    }).filter(r => r.phone);
    return { headers, rows };
  };
  const parsed = parseCsv();
  const recipients = parsed.rows;
  const headers = parsed.headers;
  const segs = Math.max(1, Math.ceil(template.length / 153));
  const totalCredits = rate * segs * recipients.length;

  // Load from a saved group
  const loadFromGroup = async (gid) => {
    setPickedGroup(gid);
    if (!gid) return;
    try {
      const { data } = await http.get("/contacts");
      const filtered = data.filter(c => (c.group_ids || []).includes(gid));
      if (!filtered.length) return toast.error("This group has no contacts yet.");
      const extraKeys = Array.from(new Set(filtered.flatMap(c => Object.keys(c.extras || {}))));
      const cols = ["phone", "name", ...extraKeys];
      const lines = [cols.join(",")];
      filtered.forEach(c => {
        const row = [c.phone, c.name || "", ...extraKeys.map(k => c.extras?.[k] || "")];
        lines.push(row.join(","));
      });
      setCsv(lines.join("\n"));
      toast.success(`Loaded ${filtered.length} contacts from group.`);
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
  };

  const [mapModal, setMapModal] = useState(null);
  // mapModal = { headers: string[], rows: [{}], defaultPhone: string | null }

  // Excel/CSV file import
  const onFile = async (e) => {
    const f = e.target.files?.[0];
    if (!f) return;
    let rows = [];
    if (f.name.match(/\.(xlsx|xls)$/i)) {
      const data = await f.arrayBuffer();
      const wb = XLSX.read(data);
      const ws = wb.Sheets[wb.SheetNames[0]];
      rows = XLSX.utils.sheet_to_json(ws, { defval: "" });
    } else {
      const text = await f.text();
      // tiny CSV parse to dict rows
      const lines = text.split(/\r?\n/).filter(Boolean);
      if (!lines.length) { toast.error("File is empty."); e.target.value = ""; return; }
      const rawHeaders = lines[0].split(",").map(h => h.trim());
      rows = lines.slice(1).map(line => {
        const parts = line.split(",").map(v => v.trim());
        const r = {};
        rawHeaders.forEach((h, i) => r[h] = parts[i] || "");
        return r;
      });
    }
    if (!rows.length) { toast.error("No rows detected."); e.target.value = ""; return; }
    const headers = Object.keys(rows[0]);
    // Try to auto-detect phone column
    const guess = headers.find(h => /^(phone|mobile|msisdn|number|contact|tel|telephone)$/i.test(h));
    if (guess) {
      applyImport(rows, headers, guess);
    } else {
      setMapModal({ headers, rows, defaultPhone: headers[0] });
    }
    e.target.value = "";
  };

  const applyImport = (rows, headers, phoneCol, columnRenames = {}) => {
    // Build new CSV with phone first + renames applied, slugged keys
    const slug = (s) => String(s).trim().replace(/\s+/g, "_").toLowerCase();
    const otherCols = headers.filter(h => h !== phoneCol);
    const finalCols = ["phone", ...otherCols.map(h => slug(columnRenames[h] || h))];
    const lines = [finalCols.join(",")];
    rows.forEach(r => {
      const vals = [String(r[phoneCol] ?? "").replace(/,/g, " ").trim()];
      otherCols.forEach(h => vals.push(String(r[h] ?? "").replace(/,/g, " ").trim()));
      lines.push(vals.join(","));
    });
    setCsv(lines.join("\n"));
    toast.success(`Loaded ${rows.length} rows · ${finalCols.length} columns.`);
  };

  // Insert variable chip at cursor
  const insertVariable = (col) => {
    const el = templateRef.current;
    const token = "{" + col + "}";
    if (!el) { setTemplate(template + token); return; }
    const start = el.selectionStart ?? template.length;
    const end = el.selectionEnd ?? template.length;
    const next = template.slice(0, start) + token + template.slice(end);
    setTemplate(next);
    setTimeout(() => {
      el.focus();
      el.selectionStart = el.selectionEnd = start + token.length;
    }, 0);
  };

  const usedVariables = useMemo(() => {
    const matches = template.match(/\{(\w+)\}/g) || [];
    return Array.from(new Set(matches.map(m => m.slice(1, -1))));
  }, [template]);

  const unknownVars = usedVariables.filter(v => !headers.includes(v));

  const submit = async (e) => {
    e.preventDefault();
    if (!name.trim()) return toast.error("Campaign needs a name.");
    if (!senderId) return toast.error("Choose an approved sender ID.");
    if (recipients.length === 0) return toast.error("CSV has no recipients.");
    if (unknownVars.length > 0)
      return toast.error(`Your message uses {${unknownVars.join("}, {")}} but that column isn't in your data.`);
    setBusy(true);
    try {
      const { data } = await http.post("/messaging/bulk-send", {
        name, channel, sender_id: senderId, recipients, template,
        schedule_at: scheduleAt || null,
      });
      toast.success(`Campaign queued · ${data.estimated_credits} credits`);
      setName("");
    } catch (err) {
      toast.error(fmtErr(err.response?.data?.detail) || err.message);
    } finally { setBusy(false); }
  };

  return (
    <div>
      <PageHeader overline="Campaign" title="Bulk send"
                  desc="Upload a spreadsheet or load a contact group, then personalise your message with merge tags like {name}, {amount_owed}."/>

      <form onSubmit={submit} className="grid gap-6 lg:grid-cols-[1fr,380px]" data-testid="bulk-send-form">
        <Card>
          <div className="grid gap-5">
            <Field label="Campaign name">
              <Input value={name} onChange={(e) => setName(e.target.value)} placeholder="January Reminders" data-testid="bulk-name"/>
            </Field>
            <div className="grid gap-3 sm:grid-cols-2">
              <Field label="Channel">
                <div className="grid grid-cols-2 gap-2">
                  {["sms", "whatsapp"].map(c => (
                    <button type="button" key={c} onClick={() => setChannel(c)}
                      data-testid={`bulk-channel-${c}`}
                      className={`h-10 border text-sm uppercase tracking-widest font-medium transition ${channel === c ? "border-white bg-white text-black" : "border-zinc-800 text-zinc-400 hover:border-zinc-600"}`}>{c}</button>
                  ))}
                </div>
              </Field>
              <Field label="Sender ID">
                <Select value={senderId} onChange={(e) => setSenderId(e.target.value)} data-testid="bulk-sid">
                  <option value="">— pick approved —</option>
                  {sids.map(s => <option key={s.id} value={s.sender_id}>{s.sender_id}</option>)}
                </Select>
              </Field>
            </div>

            <Field label="Recipients" hint="Load a contact group, upload Excel/CSV, or paste rows directly. The first row is always headers.">
              <div className="mb-2 flex flex-wrap items-center gap-2">
                <label className="inline-flex cursor-pointer items-center gap-2 border border-zinc-800 bg-zinc-950 px-3 py-1.5 text-xs text-zinc-300 hover:border-zinc-600">
                  <FileSpreadsheet className="h-3.5 w-3.5"/> Upload .xlsx / .csv
                  <input type="file" accept=".csv,.xlsx,.xls,text/csv,text/plain" onChange={onFile} className="hidden" data-testid="bulk-file"/>
                </label>
                {groups.length > 0 && (
                  <div className="inline-flex items-center gap-2">
                    <ListFilter className="h-3.5 w-3.5 text-zinc-500"/>
                    <select value={pickedGroup} onChange={(e) => loadFromGroup(e.target.value)}
                             className="border border-zinc-800 bg-zinc-950 px-3 py-1.5 text-xs text-zinc-300"
                             data-testid="bulk-load-group">
                      <option value="">Load from contact group...</option>
                      {groups.map(g => <option key={g.id} value={g.id}>{g.name}</option>)}
                    </select>
                  </div>
                )}
                <span className="font-mono text-[10px] text-zinc-500">
                  {recipients.length} valid rows · {headers.length} columns
                </span>
              </div>
              <TextArea value={csv} onChange={(e) => setCsv(e.target.value)}
                         className="min-h-[160px] font-mono text-xs" data-testid="bulk-csv"/>
            </Field>

            <Field label="Message template" hint="Click a column chip to insert it as a merge tag at your cursor.">
              {headers.length > 0 && (
                <div className="mb-2 flex flex-wrap gap-1" data-testid="var-chips">
                  <span className="text-[10px] uppercase tracking-widest text-zinc-500">merge tags:</span>
                  {headers.filter(h => h !== "phone").map(col => (
                    <button type="button" key={col} onClick={() => insertVariable(col)}
                             data-testid={`var-${col}`}
                             className="inline-flex items-center gap-1 border border-zinc-800 bg-[#141414] px-2 py-0.5 font-mono text-[11px] text-zinc-300 hover:border-white hover:text-white">
                      <Tag className="h-3 w-3"/>{col}
                    </button>
                  ))}
                </div>
              )}
              <TextArea ref={templateRef} value={template}
                         onChange={(e) => setTemplate(e.target.value)} data-testid="bulk-template"/>
              {usedVariables.length > 0 && (
                <div className="mt-2 text-[11px] text-zinc-500">
                  Using <span className="font-mono text-white">{usedVariables.length}</span> merge tag{usedVariables.length === 1 ? "" : "s"}: {usedVariables.map(v => (
                    <span key={v}
                           className={`mx-0.5 font-mono ${headers.includes(v) ? "text-emerald-400" : "text-red-400"}`}>
                      {"{" + v + "}"}
                    </span>
                  ))}
                </div>
              )}
              {unknownVars.length > 0 && (
                <div className="mt-2 border border-red-500/30 bg-red-500/5 p-2 text-[11px] text-red-300">
                  Your message uses <span className="font-mono">{"{" + unknownVars.join("}, {") + "}"}</span> but the column isn't in your data.
                </div>
              )}
            </Field>

            <Field label="Schedule (optional)">
              <Input type="datetime-local" value={scheduleAt} onChange={(e) => setScheduleAt(e.target.value)} data-testid="bulk-schedule"/>
            </Field>
          </div>
        </Card>

        <div className="space-y-4">
          <Card testid="bulk-summary">
            <div className="label-overline">Campaign summary</div>
            <div className="mt-4 grid grid-cols-2 gap-4">
              <div><div className="font-mono text-2xl font-medium">{recipients.length}</div><div className="text-xs text-zinc-500">recipients</div></div>
              <div><div className="font-mono text-2xl font-medium">{segs}</div><div className="text-xs text-zinc-500">segments / msg</div></div>
            </div>
            <div className="mt-4 border-t border-zinc-900 pt-4 text-xs text-zinc-500">
              <div className="flex justify-between"><span>Channel</span><Pill status={channel === "sms" ? "info" : "approved"}>{channel.toUpperCase()}</Pill></div>
              <div className="mt-2 flex justify-between"><span>Rate</span><span className="font-mono text-white">{rate} cr / msg</span></div>
              <div className="mt-2 flex justify-between"><span>Personalisation</span>
                <span className="font-mono text-white">{usedVariables.length} field{usedVariables.length === 1 ? "" : "s"}</span>
              </div>
              <div className="mt-2 flex justify-between"><span>Total</span><span className="font-mono text-emerald-400">{creditsShort(totalCredits)} cr</span></div>
              <div className="mt-2 flex justify-between"><span>When</span><span className="font-mono text-white">{scheduleAt ? new Date(scheduleAt).toLocaleString() : "Now"}</span></div>
            </div>
            <Btn type="submit" disabled={busy} className="mt-5 w-full" data-testid="bulk-dispatch">
              <Upload className="h-4 w-4"/> {busy ? "Queuing…" : scheduleAt ? "Schedule" : "Send now"}
            </Btn>
          </Card>
          <Card>
            <div className="label-overline">Preview (row 1)</div>
            <div className="mt-3 border border-zinc-800 bg-zinc-950 p-4 text-sm">
              {recipients.length === 0 ? <span className="text-zinc-600">Add at least one row in the CSV.</span> : (
                <>
                  <div className="font-mono text-[10px] uppercase tracking-widest text-zinc-500">TO {recipients[0].phone}</div>
                  <div className="mt-2 whitespace-pre-wrap break-words">
                    {Object.entries(recipients[0]).reduce((s, [k, v]) => s.replaceAll("{" + k + "}", v), template)}
                  </div>
                </>
              )}
            </div>
            {recipients.length > 1 && (
              <div className="mt-2 text-[11px] text-zinc-500">
                Preview cycles through your first row — every recipient gets their own personalised message.
              </div>
            )}
          </Card>
        </div>
      </form>

      <ColumnMapModal
        state={mapModal}
        onClose={() => setMapModal(null)}
        onConfirm={(phoneCol, renames) => {
          applyImport(mapModal.rows, mapModal.headers, phoneCol, renames);
          setMapModal(null);
        }}
      />
    </div>
  );
}

function ColumnMapModal({ state, onClose, onConfirm }) {
  const [phoneCol, setPhoneCol] = useState("");
  const [renames, setRenames] = useState({});

  useEffect(() => {
    if (state) {
      setPhoneCol(state.defaultPhone || state.headers[0]);
      setRenames({});
    }
  }, [state]);

  if (!state) return null;
  const sample = state.rows[0] || {};
  const suggest = (h) => {
    const l = h.toLowerCase().trim();
    const map = {
      "first name": "first_name", "firstname": "first_name",
      "last name": "last_name", "lastname": "last_name",
      "full name": "name", "customer name": "name",
      "amount due": "amount_owed", "amount owed": "amount_owed",
      "balance": "balance", "account number": "account_number",
      "due date": "due_date",
    };
    return map[l] || null;
  };

  const renameValue = (h) => renames[h] ?? suggest(h) ?? h;

  return (
    <Modal open={!!state} onClose={onClose} title="Map your columns" testid="column-map-modal">
      <p className="mb-4 text-sm text-zinc-400">
        We couldn't find a "phone" column automatically. Tell us which column holds the phone numbers and rename any others you want to use as personalisation tags.
      </p>

      <div className="mb-4 border border-zinc-900 bg-[#0e0e0e] p-3">
        <div className="label-overline mb-2">Which column is the phone number?</div>
        <Select value={phoneCol} onChange={(e) => setPhoneCol(e.target.value)} data-testid="map-phone-col">
          {state.headers.map(h => (
            <option key={h} value={h}>{h} · sample: {String(sample[h] || "").slice(0, 30)}</option>
          ))}
        </Select>
      </div>

      <div className="label-overline mb-2">Other columns — rename them into clean tags</div>
      <div className="grid max-h-72 gap-2 overflow-y-auto">
        {state.headers.filter(h => h !== phoneCol).map(h => (
          <div key={h} className="grid grid-cols-[1fr,auto,1fr] items-center gap-2 border border-zinc-900 bg-[#141414] p-2 text-sm">
            <div>
              <div className="text-white">{h}</div>
              <div className="font-mono text-[10px] text-zinc-500">
                e.g. {String(sample[h] || "—").slice(0, 28)}
              </div>
            </div>
            <span className="font-mono text-zinc-600">→</span>
            <Input value={renameValue(h)}
                   onChange={(e) => setRenames({ ...renames, [h]: e.target.value })}
                   className="font-mono text-xs"
                   data-testid={`map-rename-${h}`}/>
          </div>
        ))}
      </div>

      <div className="mt-4 flex items-center justify-between border-t border-zinc-900 pt-4">
        <span className="text-xs text-zinc-500">{state.rows.length} rows detected</span>
        <Btn onClick={() => onConfirm(phoneCol, renames)} data-testid="map-confirm">
          Import {state.rows.length} row{state.rows.length === 1 ? "" : "s"}
        </Btn>
      </div>
    </Modal>
  );
}
