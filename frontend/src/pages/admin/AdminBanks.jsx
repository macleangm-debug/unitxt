import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr } from "@/lib/api";
import { PageHeader, Field, Input, TextArea, Btn, Table, Pill, Modal, Card } from "@/components/UI";
import { Plus, Trash2, Landmark, Info } from "lucide-react";

const empty = {
  country: "",
  bank_name: "",
  account_name: "",
  account_number: "",
  branch: "",
  swift: "",
  currency: "",
  instructions: "",
  active: true,
};

export default function AdminBanks() {
  const [items, setItems] = useState([]);
  const [edit, setEdit] = useState(null);
  const [busy, setBusy] = useState(false);

  const load = () => http.get("/admin/banks").then(r => setItems(r.data)).catch(() => {});
  useEffect(() => { load(); }, []);

  const save = async (e) => {
    e.preventDefault();
    const body = {
      ...edit,
      country: (edit.country || "").trim().toUpperCase() || "*",
      currency: (edit.currency || "").trim().toUpperCase() || null,
    };
    setBusy(true);
    try {
      if (edit.id) await http.put(`/admin/banks/${edit.id}`, body);
      else await http.post("/admin/banks", body);
      toast.success(edit.id ? "Bank account updated" : "Bank account added");
      setEdit(null); load();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
    finally { setBusy(false); }
  };

  const del = async (id) => {
    if (!window.confirm("Delete this bank account? Clients won't see it anymore.")) return;
    try {
      await http.delete(`/admin/banks/${id}`);
      toast.success("Deleted"); load();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
  };

  return (
    <div>
      <PageHeader
        overline="Treasury · Configuration"
        title="Bank accounts"
        desc="Where clients can send manual bank transfers to top up credits. Scope each account to a country or mark it global."
        actions={
          <Btn onClick={() => setEdit({ ...empty })} data-testid="add-bank">
            <Plus className="h-4 w-4"/>Add bank account
          </Btn>
        }
      />

      <Card className="mb-4" testid="bank-hint">
        <div className="flex items-start gap-3 text-sm text-zinc-400">
          <Info className="mt-0.5 h-4 w-4 shrink-0 text-zinc-500" strokeWidth={1.5}/>
          <div>
            Clients from a given country see only bank accounts scoped to that country (plus any
            global ones marked <code className="font-mono text-zinc-300">*</code>). Use the
            "Instructions" field for plain-English guidance, e.g.{" "}
            <span className="italic text-zinc-300">"Use your registered email as the reference."</span>
          </div>
        </div>
      </Card>

      <Table testid="banks-table" rows={items} empty="No bank accounts configured yet." columns={[
        { key: "country",        label: "Country",    mono: true,
          render: r => r.country === "*" ? <span className="text-zinc-400">Global</span> : r.country },
        { key: "bank_name",      label: "Bank" },
        { key: "account_name",   label: "Account name" },
        { key: "account_number", label: "Account #",  mono: true },
        { key: "currency",       label: "Currency",   mono: true,
          render: r => r.currency || "—" },
        { key: "branch",         label: "Branch",     render: r => r.branch || "—" },
        { key: "active",         label: "Status",
          render: r => <Pill status={r.active ? "active" : "down"}>{r.active ? "active" : "disabled"}</Pill> },
        { key: "actions",        label: "",
          render: r => (
            <div className="flex gap-3">
              <button onClick={() => setEdit({ ...r })}
                      className="text-xs text-zinc-400 underline hover:text-white"
                      data-testid={`edit-bank-${r.id}`}>edit</button>
              <button onClick={() => del(r.id)} className="text-zinc-400 hover:text-red-400"
                      data-testid={`del-bank-${r.id}`}><Trash2 className="h-4 w-4"/></button>
            </div>
          ) },
      ]}/>

      <Modal open={!!edit} onClose={() => setEdit(null)}
              title={edit?.id ? "Edit bank account" : "Add bank account"} testid="bank-modal">
        {edit && (
          <form onSubmit={save} className="grid gap-3 sm:grid-cols-2">
            <Field label="Country (ISO-2)"
                    hint="Enter a 2-letter country code like TZ or KE, or leave empty / use * for global.">
              <Input value={edit.country}
                      onChange={(e) => setEdit({ ...edit, country: e.target.value.toUpperCase() })}
                      placeholder="TZ"
                      data-testid="bank-country"/>
            </Field>
            <Field label="Currency (optional)" hint="e.g. TZS, KES, USD. Leave empty to inherit country default.">
              <Input value={edit.currency || ""}
                      onChange={(e) => setEdit({ ...edit, currency: e.target.value.toUpperCase() })}
                      placeholder="TZS"
                      data-testid="bank-currency"/>
            </Field>
            <Field label="Bank name">
              <Input value={edit.bank_name}
                      onChange={(e) => setEdit({ ...edit, bank_name: e.target.value })}
                      required data-testid="bank-name"/>
            </Field>
            <Field label="Account name">
              <Input value={edit.account_name}
                      onChange={(e) => setEdit({ ...edit, account_name: e.target.value })}
                      required data-testid="bank-account-name"/>
            </Field>
            <Field label="Account number">
              <Input value={edit.account_number}
                      onChange={(e) => setEdit({ ...edit, account_number: e.target.value })}
                      required data-testid="bank-account-number"/>
            </Field>
            <Field label="Branch (optional)">
              <Input value={edit.branch || ""}
                      onChange={(e) => setEdit({ ...edit, branch: e.target.value })}
                      data-testid="bank-branch"/>
            </Field>
            <Field label="SWIFT / BIC (optional)">
              <Input value={edit.swift || ""}
                      onChange={(e) => setEdit({ ...edit, swift: e.target.value.toUpperCase() })}
                      placeholder="CORUTZTZ"
                      data-testid="bank-swift"/>
            </Field>
            <Field label="Status">
              <select value={edit.active ? "1" : "0"}
                       onChange={(e) => setEdit({ ...edit, active: e.target.value === "1" })}
                       className="w-full border border-zinc-800 bg-transparent px-3 py-2 text-sm text-white outline-none focus:border-white"
                       data-testid="bank-active">
                <option value="1">active — clients can see it</option>
                <option value="0">disabled — hidden from clients</option>
              </select>
            </Field>
            <div className="sm:col-span-2">
              <Field label="Instructions (shown to the client on the payment modal)"
                     hint="Plain English. Keep it short and concrete.">
                <TextArea value={edit.instructions || ""}
                           onChange={(e) => setEdit({ ...edit, instructions: e.target.value })}
                           placeholder={"Use your registered email address as the payment reference."}
                           className="min-h-[90px]"
                           data-testid="bank-instructions"/>
              </Field>
            </div>
            <div className="sm:col-span-2 mt-2 flex items-center justify-between">
              <span className="text-[11px] text-zinc-500">
                <Landmark className="mr-1 inline h-3.5 w-3.5" strokeWidth={1.5}/>
                Once saved, this bank will appear to clients who pick "Pay by bank transfer".
              </span>
              <Btn type="submit" disabled={busy} data-testid="bank-save">
                {busy ? "Saving…" : (edit.id ? "Update" : "Add bank")}
              </Btn>
            </div>
          </form>
        )}
      </Modal>
    </div>
  );
}
