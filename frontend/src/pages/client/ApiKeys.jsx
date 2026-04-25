import { useEffect, useMemo, useState } from "react";
import { Link } from "react-router-dom";
import { toast } from "sonner";
import http, { fmtErr, shortDate } from "@/lib/api";
import { PageHeader, Card, Field, Input, Btn, Table } from "@/components/UI";
import {
  Plus, Trash2, Copy, Key as KeyIcon, BookOpen, Code2, IdCard,
  CheckCircle2, AlertCircle, Webhook, Send, ArrowUpRight, Server,
} from "lucide-react";

const API_BASE =
  (process.env.REACT_APP_BACKEND_URL || "https://your-app.example.com").replace(/\/$/, "") + "/api";

const TABS = [
  { key: "keys",        label: "Your keys",        icon: KeyIcon },
  { key: "quickstart",  label: "Quick start",      icon: Send },
  { key: "endpoints",   label: "Endpoints",        icon: Server },
  { key: "sender_ids",  label: "Sender ID flow",   icon: IdCard },
];

export default function ApiKeys() {
  const [tab, setTab] = useState("keys");
  const [items, setItems] = useState([]);
  const [name, setName] = useState("");
  const load = () => http.get("/api-keys").then((r) => setItems(r.data)).catch(() => {});
  useEffect(() => { load(); }, []);

  const create = async (e) => {
    e.preventDefault();
    try {
      await http.post("/api-keys", { name });
      setName(""); load();
      toast.success("API key created");
    } catch (err) { toast.error(fmtErr(err.response?.data?.detail)); }
  };
  const del = async (id) => { await http.delete(`/api-keys/${id}`); load(); };
  const copy = (k) => { navigator.clipboard.writeText(k); toast.success("Copied"); };

  const sampleKey = useMemo(
    () => items[0]?.key || "YOUR_API_KEY",
    [items]
  );

  return (
    <div>
      <PageHeader
        overline="Programmatic access"
        title="API & integration"
        desc="Generate keys, browse endpoints, and connect your CRM, banking core, or website in minutes."
        actions={
          <a
            href="/api/docs" target="_blank" rel="noreferrer"
            className="inline-flex items-center gap-1.5 text-sm text-blue-400 hover:text-blue-300"
            data-testid="open-swagger"
          >
            Swagger reference <ArrowUpRight className="h-3.5 w-3.5" />
          </a>
        }
      />

      {/* Tabs */}
      <div className="mb-5 flex flex-wrap gap-1 rounded-lg border border-zinc-800/80 bg-[#14171C] p-1" data-testid="api-tabs">
        {TABS.map((t) => (
          <button
            key={t.key}
            onClick={() => setTab(t.key)}
            data-testid={`api-tab-${t.key}`}
            className={`inline-flex flex-1 items-center justify-center gap-2 rounded-md px-3 py-2 text-xs font-medium transition sm:flex-none sm:text-[13px] ${
              tab === t.key
                ? "bg-blue-500/15 text-zinc-100 ring-1 ring-inset ring-blue-500/30"
                : "text-zinc-400 hover:bg-white/[0.04] hover:text-zinc-100"
            }`}
          >
            <t.icon className="h-3.5 w-3.5" strokeWidth={1.8} />
            <span className="hidden sm:inline">{t.label}</span>
            <span className="sm:hidden">{t.label.split(" ")[0]}</span>
          </button>
        ))}
      </div>

      {tab === "keys" && (
        <KeysTab items={items} create={create} name={name} setName={setName} copy={copy} del={del} />
      )}
      {tab === "quickstart" && <QuickstartTab apiKey={sampleKey} />}
      {tab === "endpoints" && <EndpointsTab apiKey={sampleKey} />}
      {tab === "sender_ids" && <SenderIdTab apiKey={sampleKey} />}
    </div>
  );
}

/* ============================================================
   1) Your keys
   ============================================================ */
function KeysTab({ items, create, name, setName, copy, del }) {
  return (
    <>
      <Card>
        <form onSubmit={create} className="grid gap-3 sm:grid-cols-[1fr,auto]">
          <Field label="Key name" hint="Give it a recognizable name like 'Production CRM' or 'Staging webhook'.">
            <Input
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="Production CRM"
              required
              data-testid="key-name"
            />
          </Field>
          <div className="flex items-end">
            <Btn type="submit" data-testid="key-create" className="w-full sm:w-auto">
              <Plus className="h-4 w-4" />Create key
            </Btn>
          </div>
        </form>
      </Card>

      <h2 className="label-overline mt-7 mb-3">Existing keys</h2>

      {/* Desktop table */}
      <div className="hidden md:block">
        <Table testid="keys-table" rows={items} empty="No API keys yet — create one above." columns={[
          { key: "name", label: "Name" },
          { key: "key", label: "Key", render: (r) => <code className="font-mono text-xs text-zinc-300">{r.key}</code> },
          { key: "created_at", label: "Created", mono: true, render: (r) => shortDate(r.created_at) },
          {
            key: "actions", label: "",
            render: (r) => (
              <div className="flex justify-end gap-2">
                <button onClick={() => copy(r.key)} className="rounded p-1.5 text-zinc-400 hover:bg-white/5 hover:text-white" data-testid={`copy-${r.id}`}><Copy className="h-4 w-4" /></button>
                <button onClick={() => del(r.id)} className="rounded p-1.5 text-zinc-400 hover:bg-red-500/10 hover:text-red-400" data-testid={`del-key-${r.id}`}><Trash2 className="h-4 w-4" /></button>
              </div>
            ),
          },
        ]}/>
      </div>

      {/* Mobile cards */}
      <div className="space-y-2 md:hidden">
        {items.length === 0 && (
          <div className="rounded-lg border border-dashed border-zinc-800 px-6 py-8 text-center text-sm text-zinc-500">
            No API keys yet — create one above.
          </div>
        )}
        {items.map((r) => (
          <div key={r.id} className="rounded-lg border border-zinc-800/80 bg-[#14171C] p-4">
            <div className="flex items-start justify-between gap-3">
              <div className="min-w-0">
                <div className="text-sm font-medium text-zinc-100">{r.name}</div>
                <div className="mt-0.5 text-[11px] text-zinc-500">{shortDate(r.created_at)}</div>
              </div>
              <div className="flex items-center gap-1">
                <button onClick={() => copy(r.key)} className="rounded p-1.5 text-zinc-400 hover:bg-white/5 hover:text-white"><Copy className="h-4 w-4" /></button>
                <button onClick={() => del(r.id)} className="rounded p-1.5 text-zinc-400 hover:bg-red-500/10 hover:text-red-400"><Trash2 className="h-4 w-4" /></button>
              </div>
            </div>
            <code className="mt-2 block break-all rounded bg-zinc-950/60 px-2 py-1.5 font-mono text-[11px] text-zinc-300">{r.key}</code>
          </div>
        ))}
      </div>
    </>
  );
}

/* ============================================================
   2) Quick start
   ============================================================ */
function QuickstartTab({ apiKey }) {
  return (
    <div className="grid gap-4">
      <InfoBanner
        icon={CheckCircle2}
        tone="blue"
        title="First 200 OK in under a minute."
        body={<>Authenticate with a Bearer token, send a test SMS, and you're live. Use the snippets below — they're pre-filled with your endpoint.</>}
      />

      <Card>
        <SectionTitle index="01" title="Authenticate" desc="Every request needs your API key in the Authorization header." />
        <CodeBlock
          lang="http"
          code={`Authorization: Bearer ${apiKey}\nContent-Type: application/json`}
        />
      </Card>

      <Card>
        <SectionTitle index="02" title="Send your first SMS" desc="Replace the recipient and the sender ID with your approved one." />
        <Tabs
          tabs={[
            {
              label: "cURL",
              code: `curl -X POST ${API_BASE}/messaging/quick-send \\
  -H "Authorization: Bearer ${apiKey}" \\
  -H "Content-Type: application/json" \\
  -d '{
    "to": "+255712345678",
    "message": "Hi from your CRM",
    "channel": "sms",
    "sender_id": "YOUR_APPROVED_SENDER_ID"
  }'`,
            },
            {
              label: "Python",
              code: `import requests

r = requests.post(
    "${API_BASE}/messaging/quick-send",
    headers={"Authorization": "Bearer ${apiKey}"},
    json={
        "to": "+255712345678",
        "message": "Hi from your CRM",
        "channel": "sms",
        "sender_id": "YOUR_APPROVED_SENDER_ID",
    },
)
print(r.status_code, r.json())`,
            },
            {
              label: "Node",
              code: `const res = await fetch("${API_BASE}/messaging/quick-send", {
  method: "POST",
  headers: {
    Authorization: \`Bearer ${apiKey}\`,
    "Content-Type": "application/json",
  },
  body: JSON.stringify({
    to: "+255712345678",
    message: "Hi from your CRM",
    channel: "sms",
    sender_id: "YOUR_APPROVED_SENDER_ID",
  }),
});
console.log(await res.json());`,
            },
          ]}
        />
      </Card>

      <Card>
        <SectionTitle index="03" title="Check your balance" />
        <CodeBlock
          lang="bash"
          code={`curl ${API_BASE}/credits/rates -H "Authorization: Bearer ${apiKey}"`}
        />
      </Card>

      <Card className="border-blue-500/20 bg-blue-500/[0.03]">
        <div className="flex items-start gap-3">
          <BookOpen className="mt-0.5 h-4 w-4 text-blue-400" strokeWidth={1.8} />
          <div>
            <div className="text-sm font-medium text-zinc-100">Common integrations</div>
            <p className="mt-1 text-sm text-zinc-400">
              <strong>Banks &amp; mobile-money</strong>: trigger an SMS the moment a transaction posts —
              hook it into your core banking webhook.
              <strong> CRMs</strong> (Salesforce, HubSpot, Zoho): send drip campaigns or transactional alerts via the bulk endpoint.
              <strong> E-commerce</strong> (Shopify, WooCommerce): order-status SMS using merge tags like <code className="rounded bg-white/5 px-1 py-0.5 font-mono text-[11px]">{`{order_id}`}</code>.
              See the <button onClick={() => window.scrollTo({ top: 0 })} className="font-medium text-blue-400 underline-offset-2 hover:underline">Endpoints</button> tab for the full surface.
            </p>
          </div>
        </div>
      </Card>
    </div>
  );
}

/* ============================================================
   3) Endpoints
   ============================================================ */
const ENDPOINT_GROUPS = [
  {
    name: "Messaging",
    icon: Send,
    items: [
      { method: "POST", path: "/messaging/quick-send",     desc: "Send a single SMS or WhatsApp message." },
      { method: "POST", path: "/messaging/bulk-send",       desc: "Queue up to 100k+ recipients with merge tags. Same body as quick-send but with a `recipients[]` array." },
      { method: "POST", path: "/messaging/preflight",       desc: "Free quality check on a sample of numbers — returns deliverability % and suggested actions." },
      { method: "GET",  path: "/messaging/campaigns",       desc: "List your recent campaigns (paginated)." },
      { method: "GET",  path: "/messaging/delivery-report", desc: "Aggregated delivery stats with top failure reasons." },
    ],
  },
  {
    name: "Sender IDs",
    icon: IdCard,
    items: [
      { method: "POST", path: "/sender-ids",      desc: "Submit a new sender ID for approval (status starts as `pending`)." },
      { method: "GET",  path: "/sender-ids",      desc: "List your sender IDs and current status (`pending` / `approved` / `rejected`)." },
      { method: "POST", path: "/sender-ids/{id}/renew", desc: "Renew an expiring sender ID (charges renewal cost)." },
    ],
  },
  {
    name: "Number lookup",
    icon: AlertCircle,
    items: [
      { method: "POST", path: "/numbers/validate",        desc: "Smart-validate a single number (format, country, operator, your delivery history)." },
      { method: "POST", path: "/numbers/validate/bulk",   desc: "Validate up to 5,000 numbers in one batch (charged per number)." },
      { method: "GET",  path: "/numbers/services",        desc: "What lookup services are live + their per-call cost." },
    ],
  },
  {
    name: "Wallet",
    icon: KeyIcon,
    items: [
      { method: "GET",  path: "/credits/rates",       desc: "Per-country SMS rate, WhatsApp rate, sender-ID cost, recovery cost." },
      { method: "GET",  path: "/wallet/transactions", desc: "Audit trail of every credit movement on your wallet." },
      { method: "GET",  path: "/credits/packs",       desc: "Available credit packs with local-currency pricing." },
    ],
  },
  {
    name: "Webhooks (delivery reports)",
    icon: Webhook,
    items: [
      { method: "POST", path: "{your_url}", desc: "We POST { campaign_id, message_id, to, status, reason, delivered_at } as soon as the operator confirms. Configure the URL on the Webhooks page." },
    ],
  },
];

function EndpointsTab({ apiKey }) {
  const [search, setSearch] = useState("");
  return (
    <div className="grid gap-4">
      <div className="flex items-center gap-3">
        <Input
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder="Search endpoints…"
          className="w-full md:max-w-sm"
          data-testid="endpoints-search"
        />
      </div>

      {ENDPOINT_GROUPS.map((g) => {
        const filtered = g.items.filter(
          (i) =>
            !search ||
            i.path.toLowerCase().includes(search.toLowerCase()) ||
            i.desc.toLowerCase().includes(search.toLowerCase()) ||
            g.name.toLowerCase().includes(search.toLowerCase())
        );
        if (filtered.length === 0) return null;
        return (
          <Card key={g.name}>
            <div className="mb-3 flex items-center gap-2">
              <g.icon className="h-4 w-4 text-blue-400" strokeWidth={1.8} />
              <h3 className="font-display text-sm font-semibold uppercase tracking-wider text-zinc-300">
                {g.name}
              </h3>
            </div>
            <div className="space-y-2">
              {filtered.map((i) => (
                <EndpointRow key={i.method + i.path} {...i} apiKey={apiKey} />
              ))}
            </div>
          </Card>
        );
      })}
    </div>
  );
}

function EndpointRow({ method, path, desc, apiKey }) {
  const [open, setOpen] = useState(false);
  const methodColor =
    method === "GET"    ? "bg-emerald-500/10 text-emerald-400 border-emerald-500/30"
    : method === "POST" ? "bg-blue-500/10 text-blue-400 border-blue-500/30"
    : "bg-amber-500/10 text-amber-400 border-amber-500/30";
  return (
    <div className="rounded-md border border-zinc-800/80 bg-zinc-950/30">
      <button
        type="button"
        onClick={() => setOpen((o) => !o)}
        className="flex w-full items-center gap-3 px-3 py-2.5 text-left transition hover:bg-white/[0.02]"
      >
        <span className={`shrink-0 rounded border px-1.5 py-0.5 font-mono text-[10px] font-semibold ${methodColor}`}>
          {method}
        </span>
        <code className="min-w-0 flex-1 truncate font-mono text-[12px] text-zinc-200">{path}</code>
        <span className="hidden text-xs text-zinc-500 md:block truncate max-w-md">{desc}</span>
      </button>
      {open && (
        <div className="border-t border-zinc-800/80 p-3">
          <p className="mb-3 text-sm text-zinc-400">{desc}</p>
          <CodeBlock
            lang="bash"
            code={`curl -X ${method === "GET" ? "GET" : method} ${API_BASE}${path.replace("{your_url}", "https://your.app/webhook")} \\
  -H "Authorization: Bearer ${apiKey}"${method !== "GET" && !path.startsWith("{") ? " \\\n  -H \"Content-Type: application/json\" \\\n  -d '{ ... }'" : ""}`}
          />
        </div>
      )}
    </div>
  );
}

/* ============================================================
   4) Sender ID lifecycle
   ============================================================ */
function SenderIdTab({ apiKey }) {
  return (
    <div className="grid gap-4">
      <InfoBanner
        icon={IdCard}
        tone="blue"
        title="How sender ID approval works"
        body={
          <>
            Sender IDs are the brand name your recipients see (e.g. <strong>SUNRISE</strong>, <strong>DBS-BANK</strong>).
            Most countries require operator-level approval. unitxt handles that for you — submit once, we coordinate with the operators, and the status flips to <code className="rounded bg-white/5 px-1 py-0.5 font-mono text-[11px]">approved</code> when you can start sending.
          </>
        }
      />

      <div className="grid gap-4 md:grid-cols-3">
        <StepCard step="01" title="Submit" body="POST your sender ID, country, and use case. We charge the registration cost (default 500 credits) only after approval." />
        <StepCard step="02" title="Review" body="Our compliance team checks your business documents and submits to the operators. Typical turnaround: 1–3 business days. You'll get a notification on every status change." />
        <StepCard step="03" title="Send" body="Once status='approved', start sending — the sender ID will appear as the FROM in every message. Renewal happens yearly at the same cost." />
      </div>

      <Card>
        <SectionTitle index="A" title="Submit a sender ID" desc="Use the same endpoint your CRM or banking core would call." />
        <CodeBlock
          lang="bash"
          code={`curl -X POST ${API_BASE}/sender-ids \\
  -H "Authorization: Bearer ${apiKey}" \\
  -H "Content-Type: application/json" \\
  -d '{
    "sender_id": "DBS-BANK",
    "country": "TZ",
    "use_case": "Transaction alerts",
    "sample_message": "Dear customer, your account was debited TZS 50,000 on 21-Apr at 10:32. Ref: TX9281."
  }'`}
        />
      </Card>

      <Card>
        <SectionTitle index="B" title="Poll for approval status" desc="Or wait for the notification webhook — same data either way." />
        <CodeBlock
          lang="bash"
          code={`curl ${API_BASE}/sender-ids \\
  -H "Authorization: Bearer ${apiKey}"

# Response:
# [
#   {
#     "id": "...",
#     "sender_id": "DBS-BANK",
#     "country": "TZ",
#     "status": "pending",        <-- "approved" | "rejected" | "pending"
#     "created_at": "2026-04-21T10:32:00Z",
#     "expires_at": null
#   }
# ]`}
        />
      </Card>

      <Card>
        <SectionTitle index="C" title="Renew before expiry" desc="Approved sender IDs expire after 365 days. We notify you 30 days ahead." />
        <CodeBlock
          lang="bash"
          code={`curl -X POST ${API_BASE}/sender-ids/{id}/renew \\
  -H "Authorization: Bearer ${apiKey}"`}
        />
      </Card>

      <Card className="border-blue-500/20 bg-blue-500/[0.03]">
        <div className="flex items-start justify-between gap-3">
          <div className="flex items-start gap-3">
            <BookOpen className="mt-0.5 h-4 w-4 text-blue-400" strokeWidth={1.8} />
            <div>
              <div className="text-sm font-medium text-zinc-100">Prefer the dashboard?</div>
              <p className="mt-1 text-sm text-zinc-400">
                Manage every sender ID, see approval history, and re-submit rejected ones in one place.
              </p>
            </div>
          </div>
          <Link
            to="/client/sender-ids"
            className="inline-flex items-center gap-1.5 rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-blue-500 self-start"
            data-testid="open-sender-ids"
          >
            Open Sender IDs <ArrowUpRight className="h-3.5 w-3.5" />
          </Link>
        </div>
      </Card>
    </div>
  );
}

/* ============================================================
   Helpers
   ============================================================ */
function SectionTitle({ index, title, desc }) {
  return (
    <div className="mb-3">
      <div className="flex items-baseline gap-3">
        <span className="font-mono text-[10px] font-semibold tracking-wider text-blue-400">STEP {index}</span>
        <h3 className="text-sm font-semibold text-zinc-100">{title}</h3>
      </div>
      {desc && <p className="mt-1 text-sm text-zinc-400">{desc}</p>}
    </div>
  );
}

function CodeBlock({ code, lang = "bash" }) {
  const [copied, setCopied] = useState(false);
  const onCopy = () => {
    navigator.clipboard?.writeText(code);
    setCopied(true); setTimeout(() => setCopied(false), 1500);
    toast.success("Copied to clipboard");
  };
  return (
    <div className="group relative overflow-hidden rounded-md border border-zinc-800/80 bg-zinc-950">
      <div className="flex items-center justify-between border-b border-zinc-800/80 bg-zinc-900/40 px-3 py-1.5">
        <span className="font-mono text-[10px] uppercase tracking-wider text-zinc-500">{lang}</span>
        <button
          onClick={onCopy}
          className="inline-flex items-center gap-1.5 rounded px-2 py-0.5 text-[11px] text-zinc-400 transition hover:bg-white/5 hover:text-zinc-100"
        >
          {copied ? <CheckCircle2 className="h-3 w-3 text-emerald-400" /> : <Copy className="h-3 w-3" />}
          {copied ? "Copied" : "Copy"}
        </button>
      </div>
      <pre className="overflow-x-auto p-3 text-[12px] leading-relaxed">
        <code className="font-mono text-zinc-200">{code}</code>
      </pre>
    </div>
  );
}

function Tabs({ tabs }) {
  const [active, setActive] = useState(tabs[0]?.label);
  const cur = tabs.find((t) => t.label === active);
  return (
    <div>
      <div className="mb-2 flex flex-wrap gap-1">
        {tabs.map((t) => (
          <button
            key={t.label}
            onClick={() => setActive(t.label)}
            className={`rounded-md px-3 py-1.5 text-xs font-medium transition ${
              active === t.label
                ? "bg-blue-500/15 text-zinc-100 ring-1 ring-inset ring-blue-500/30"
                : "text-zinc-400 hover:bg-white/[0.04] hover:text-zinc-100"
            }`}
          >
            {t.label}
          </button>
        ))}
      </div>
      <CodeBlock lang={cur.label.toLowerCase()} code={cur.code} />
    </div>
  );
}

function InfoBanner({ icon: Icon, tone = "blue", title, body }) {
  const tones = {
    blue: "border-blue-500/20 bg-blue-500/[0.04] text-blue-300",
  };
  return (
    <div className={`rounded-lg border p-4 ${tones[tone]}`}>
      <div className="flex items-start gap-3">
        <Icon className="mt-0.5 h-4 w-4 shrink-0" strokeWidth={1.8} />
        <div className="min-w-0">
          <div className="text-sm font-medium text-zinc-100">{title}</div>
          <p className="mt-1 text-sm text-zinc-400">{body}</p>
        </div>
      </div>
    </div>
  );
}

function StepCard({ step, title, body }) {
  return (
    <div className="card-surface p-5">
      <div className="font-mono text-[10px] font-semibold tracking-wider text-blue-400">STEP {step}</div>
      <div className="mt-2 font-display text-base font-semibold text-zinc-100">{title}</div>
      <p className="mt-2 text-sm leading-relaxed text-zinc-400">{body}</p>
    </div>
  );
}
