import { useEffect, useState } from "react";
import { toast } from "sonner";
import http, { fmtErr, shortDate } from "@/lib/api";
import { PageHeader, Card, Field, Input, Btn, Pill, Modal } from "@/components/UI";
import { Tag, Plus, Trash2, Sparkles, Lock, Copy, CheckCircle2 } from "lucide-react";

export default function AffiliateCodes() {
  const [me, setMe] = useState(null);
  const [codes, setCodes] = useState([]);
  const [renaming, setRenaming] = useState(false);
  const [newName, setNewName] = useState("");
  const [busy, setBusy] = useState(false);
  const [adding, setAdding] = useState(false);
  const [newCode, setNewCode] = useState({ code: "", note: "" });
  const [copied, setCopied] = useState(null);

  const load = () => Promise.all([
    http.get("/affiliate/me"),
    http.get("/affiliate/codes"),
  ]).then(([m, c]) => { setMe(m.data); setCodes(c.data || []); }).catch(() => {});

  useEffect(() => { load(); }, []);

  const renamePrimary = async (e) => {
    e.preventDefault();
    setBusy(true);
    try {
      await http.post("/affiliate/primary-code", { code: newName.trim().toUpperCase() });
      toast.success("Primary code updated");
      setRenaming(false); setNewName("");
      load();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
    finally { setBusy(false); }
  };

  const addCode = async (e) => {
    e.preventDefault();
    setBusy(true);
    try {
      await http.post("/affiliate/codes", {
        code: newCode.code.trim().toUpperCase(),
        note: newCode.note,
      });
      toast.success("Code created");
      setAdding(false); setNewCode({ code: "", note: "" });
      load();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
    finally { setBusy(false); }
  };

  const deleteCode = async (id) => {
    if (!window.confirm("Delete this code? Existing attributions still count.")) return;
    try {
      await http.delete(`/affiliate/codes/${id}`);
      toast.success("Deleted"); load();
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
  };

  const copy = (code) => {
    navigator.clipboard?.writeText(code);
    setCopied(code); setTimeout(() => setCopied(null), 1500);
  };

  if (!me) return <div className="text-zinc-500">Loading…</div>;
  const cap = me.config?.max_codes_per_user || 5;
  const primary = codes.find(c => c.code === me.primary_code);
  const others = codes.filter(c => c.code !== me.primary_code);

  return (
    <div>
      <PageHeader
        overline="Affiliate workspace"
        title="Promo codes"
        desc="Your primary code is the one we hand out by default. Customise it once to make it yours, then mint up to 5 extra codes for tracking different channels."
      />

      {/* PRIMARY CODE */}
      <Card className="mb-5" testid="primary-code-card">
        <div className="flex items-start justify-between gap-3">
          <div>
            <div className="label-overline flex items-center gap-1.5 text-blue-400">
              <Tag className="h-3 w-3"/> Primary promo code
            </div>
            <div className="mt-2 flex items-baseline gap-3">
              <span className="font-mono text-3xl font-medium tracking-tight text-zinc-100" data-testid="primary-code-value">
                {me.primary_code || "—"}
              </span>
              {me.primary_code_renamed
                ? <Pill status="success">customised · locked</Pill>
                : <Pill status="info">unitxt default</Pill>}
            </div>
            <p className="mt-2 max-w-2xl text-xs leading-relaxed text-zinc-500">
              {me.primary_code_renamed
                ? "Your primary code is now permanent. To experiment with other codes, mint a secondary code below."
                : "We started you off with this auto-generated code. You get one chance to rename it — pick something memorable, easy to type, easy to share."}
            </p>
          </div>
          <div className="flex flex-col items-end gap-2">
            <button
              onClick={() => copy(me.primary_code)}
              disabled={!me.primary_code}
              className="inline-flex items-center gap-1.5 rounded-md border border-zinc-800 bg-zinc-900/60 px-2.5 py-1.5 text-[11px] text-zinc-300 transition hover:bg-zinc-800"
              data-testid="copy-primary-code"
            >
              {copied === me.primary_code ? <CheckCircle2 className="h-3 w-3 text-emerald-400"/> : <Copy className="h-3 w-3"/>}
              {copied === me.primary_code ? "Copied" : "Copy"}
            </button>
            {!me.primary_code_renamed && (
              <Btn onClick={() => { setRenaming(true); setNewName(me.primary_code || ""); }} data-testid="rename-primary-btn">
                <Sparkles className="h-4 w-4"/>Customise (one-time)
              </Btn>
            )}
            {me.primary_code_renamed && (
              <span className="inline-flex items-center gap-1.5 text-[11px] text-zinc-500">
                <Lock className="h-3 w-3"/> Locked
              </span>
            )}
          </div>
        </div>
      </Card>

      {/* SECONDARY CODES */}
      <div className="mb-3 flex items-end justify-between gap-3">
        <div>
          <div className="label-overline">Secondary codes</div>
          <h2 className="mt-1 font-display text-base font-semibold">
            For channel-level tracking
          </h2>
          <p className="text-xs text-zinc-500">
            Use one per channel (Twitter, WhatsApp, podcast). Up to {cap - 1} extra.
          </p>
        </div>
        <Btn
          onClick={() => setAdding(true)}
          disabled={others.length >= cap - 1}
          data-testid="add-code-btn"
        >
          <Plus className="h-4 w-4"/>New code
        </Btn>
      </div>

      {others.length === 0 ? (
        <Card className="border-dashed">
          <div className="py-6 text-center text-sm text-zinc-500">
            No secondary codes yet. Add one for tracking a specific channel.
          </div>
        </Card>
      ) : (
        <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
          {others.map(c => (
            <Card key={c.id} className="flex flex-col" testid={`code-${c.id}`}>
              <div className="flex items-start justify-between gap-2">
                <span className="font-mono text-lg font-medium text-zinc-100">{c.code}</span>
                <button
                  onClick={() => deleteCode(c.id)}
                  className="rounded p-1 text-zinc-500 hover:bg-red-500/10 hover:text-red-400"
                  data-testid={`del-code-${c.id}`}
                >
                  <Trash2 className="h-3.5 w-3.5"/>
                </button>
              </div>
              {c.note && <div className="mt-1 text-[11px] text-zinc-500">{c.note}</div>}
              <div className="mt-3 flex items-center justify-between text-[11px]">
                <span className="text-zinc-500">{c.uses || 0} use{c.uses === 1 ? "" : "s"} · {shortDate(c.created_at)}</span>
                <button onClick={() => copy(c.code)} className="text-blue-400 hover:text-blue-300">
                  {copied === c.code ? "Copied" : "Copy"}
                </button>
              </div>
            </Card>
          ))}
        </div>
      )}

      {/* RENAME MODAL */}
      <Modal open={renaming} onClose={() => setRenaming(false)} title="Customise your primary code" testid="rename-modal">
        <form onSubmit={renamePrimary} className="space-y-4">
          <div className="rounded-md border border-amber-500/25 bg-amber-500/[0.05] p-3 text-[12px] leading-relaxed text-amber-200/90">
            <strong className="text-amber-300">Heads-up:</strong> you can only do this once. After saving, the primary code is locked.
            You can still mint secondary codes any time.
          </div>
          <Field label="New primary code" hint="3–24 characters. Letters, numbers, no spaces. Case is auto-uppercased.">
            <Input
              value={newName}
              onChange={(e) => setNewName(e.target.value.toUpperCase().replace(/\s+/g, ""))}
              placeholder="e.g. JOHN20"
              required minLength={3} maxLength={24}
              autoFocus
              data-testid="rename-input"
            />
          </Field>
          <div className="flex gap-2">
            <Btn variant="ghost" type="button" onClick={() => setRenaming(false)} className="flex-1">Cancel</Btn>
            <Btn type="submit" disabled={busy} className="flex-1" data-testid="rename-submit">
              {busy ? "Saving…" : "Customise & lock"}
            </Btn>
          </div>
        </form>
      </Modal>

      {/* NEW SECONDARY CODE */}
      <Modal open={adding} onClose={() => setAdding(false)} title="New secondary code" testid="add-code-modal">
        <form onSubmit={addCode} className="space-y-4">
          <Field label="Code" hint="3–24 characters, no spaces.">
            <Input
              value={newCode.code}
              onChange={(e) => setNewCode({ ...newCode, code: e.target.value.toUpperCase().replace(/\s+/g, "") })}
              required minLength={3} maxLength={24}
              placeholder="e.g. JOHN-PODCAST"
              autoFocus
              data-testid="add-code-input"
            />
          </Field>
          <Field label="Note (optional)" hint="Where you'll use this code — for your records.">
            <Input
              value={newCode.note}
              onChange={(e) => setNewCode({ ...newCode, note: e.target.value })}
              placeholder="Tech podcast — Episode 14"
              data-testid="add-code-note"
            />
          </Field>
          <div className="flex gap-2">
            <Btn variant="ghost" type="button" onClick={() => setAdding(false)} className="flex-1">Cancel</Btn>
            <Btn type="submit" disabled={busy} className="flex-1" data-testid="add-code-submit">
              {busy ? "Adding…" : "Add code"}
            </Btn>
          </div>
        </form>
      </Modal>
    </div>
  );
}
