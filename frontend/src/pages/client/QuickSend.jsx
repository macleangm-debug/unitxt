import { useEffect, useState, useMemo } from "react";
import { toast } from "sonner";
import http, { fmtErr, creditsShort } from "@/lib/api";
import { useAuth } from "@/context/AuthContext";
import { PageHeader, Card, Input, TextArea, Select, Btn, Pill } from "@/components/UI";
import { Send, ArrowRight, ArrowLeft, Check, Plus, Sparkles } from "lucide-react";

/**
 * Conversational, humanistic Quick Send.
 * Steps: 1) Greeting + confirm identity, 2) Recipients, 3) Message, 4) Review & dispatch.
 */
export default function QuickSend() {
  const { user, wallet } = useAuth();
  const firstName = user?.name?.split(" ")[0] || "friend";

  const [step, setStep] = useState(1);
  const [channel, setChannel] = useState("sms");
  const [senderId, setSenderId] = useState("");
  const [recipients, setRecipients] = useState([]);
  const [phoneInput, setPhoneInput] = useState("");
  const [message, setMessage] = useState("");
  const [sids, setSids] = useState([]);
  const [rates, setRates] = useState(null);
  const [busy, setBusy] = useState(false);
  const [done, setDone] = useState(null); // {credits, total, name}

  useEffect(() => {
    Promise.all([http.get("/sender-ids"), http.get("/credits/rates")])
      .then(([s, r]) => {
        const approved = s.data.filter((x) => x.status === "approved");
        setSids(approved);
        if (approved[0]) setSenderId(approved[0].sender_id);
        setRates(r.data);
      })
      .catch(() => {});
  }, []);

  const rate = useMemo(() => {
    if (!rates) return 1;
    if (channel === "whatsapp") return rates.whatsapp_rate || 3;
    return rates.country_rate?.[user?.country] || rates.default_rate || 2;
  }, [rates, channel, user]);

  const segs = Math.max(1, Math.ceil((message.length || 1) / 153));
  const totalCredits = rate * segs * recipients.length;

  const addPhone = () => {
    const raw = phoneInput.trim();
    if (!raw) return;
    const parts = raw.split(/[\s,;\n]+/).filter(Boolean);
    setRecipients([...new Set([...recipients, ...parts])]);
    setPhoneInput("");
  };

  const removePhone = (p) => setRecipients(recipients.filter((x) => x !== p));

  const next = () => {
    if (step === 1 && !senderId) return toast.error("Pick a sender ID first.");
    if (step === 2 && recipients.length === 0) return toast.error("Add at least one number.");
    if (step === 3 && !message.trim()) return toast.error("Type something friendly.");
    setStep(step + 1);
  };
  const back = () => setStep(Math.max(1, step - 1));

  const dispatch = async () => {
    setBusy(true);
    try {
      const { data } = await http.post("/messaging/quick-send", {
        channel, sender_id: senderId, recipients, message,
      });
      setDone({ credits: data.estimated_credits, total: recipients.length, name: firstName });
      setStep(5);
    } catch (err) {
      toast.error(fmtErr(err.response?.data?.detail) || err.message);
    } finally { setBusy(false); }
  };

  const startOver = () => {
    setRecipients([]); setMessage(""); setPhoneInput("");
    setDone(null); setStep(1);
  };

  return (
    <div>
      <PageHeader overline="Composer"
        title={`Hi ${firstName} — let's send something.`}
        desc="We'll walk you through it in a few friendly steps."
        testid="quick-send-header"/>

      {/* Progress strip */}
      <div className="mb-6 grid grid-cols-4 gap-2" data-testid="quick-steps">
        {["Pick line", "Who to reach", "What to say", "Review"].map((l, i) => {
          const n = i + 1;
          const done = step > n || step === 5;
          const active = step === n;
          return (
            <div key={l} className={`border-t-2 pt-3 ${done?"border-emerald-500":active?"border-white":"border-zinc-800"}`}>
              <div className={`font-mono text-[10px] uppercase tracking-widest ${active?"text-white":done?"text-emerald-400":"text-zinc-600"}`}>STEP {n}</div>
              <div className={`mt-1 text-sm ${active?"text-white":done?"text-zinc-300":"text-zinc-600"}`}>{l}</div>
            </div>
          );
        })}
      </div>

      {/* STEP 1: Channel + Sender ID */}
      {step === 1 && (
        <Card testid="step-1">
          <div className="label-overline">Step 1 · The line</div>
          <h2 className="mt-2 font-display text-2xl font-semibold tracking-tight">
            Hey {firstName}. Which channel today?
          </h2>
          <p className="mt-1 text-sm text-zinc-500">Pick the channel and the sender name that will appear on the recipient's phone.</p>

          <div className="mt-6 grid gap-3 sm:grid-cols-2">
            {[
              ["sms", "SMS", "Works on any phone. One credit per segment in Tanzania."],
              ["whatsapp", "WhatsApp", "Rich but requires a WhatsApp-approved number."],
            ].map(([v, l, d]) => (
              <button
                key={v}
                type="button"
                onClick={() => setChannel(v)}
                data-testid={`qs-channel-${v}`}
                className={`border p-4 text-left transition ${channel===v?"border-white bg-white text-black":"border-zinc-800 hover:border-zinc-600"}`}
              >
                <div className="font-display text-lg font-semibold">{l}</div>
                <div className={`mt-1 text-xs ${channel===v?"text-zinc-700":"text-zinc-500"}`}>{d}</div>
              </button>
            ))}
          </div>

          <div className="mt-6">
            <div className="label-overline">Sender ID</div>
            <Select value={senderId} onChange={(e)=>setSenderId(e.target.value)} className="mt-2" data-testid="qs-sid">
              <option value="">— choose an approved one —</option>
              {sids.map(s => <option key={s.id} value={s.sender_id}>{s.sender_id} ({s.country})</option>)}
            </Select>
            {sids.length === 0 && (
              <div className="mt-3 text-xs text-zinc-500">No approved sender IDs yet. <a href="/client/sender-ids" className="text-white underline">Request one</a> first.</div>
            )}
          </div>

          <div className="mt-8 flex justify-end">
            <Btn onClick={next} data-testid="qs-next-1">Continue <ArrowRight className="h-4 w-4"/></Btn>
          </div>
        </Card>
      )}

      {/* STEP 2: Recipients */}
      {step === 2 && (
        <Card testid="step-2">
          <div className="label-overline">Step 2 · Audience</div>
          <h2 className="mt-2 font-display text-2xl font-semibold tracking-tight">
            Great. Who are we reaching, {firstName}?
          </h2>
          <p className="mt-1 text-sm text-zinc-500">Add one number or paste many. We'll keep them tidy.</p>

          <div className="mt-6 flex gap-2">
            <Input
              value={phoneInput}
              onChange={(e) => setPhoneInput(e.target.value)}
              placeholder="+255712345678  (or paste many separated by commas / newlines)"
              onKeyDown={(e)=>{if(e.key==="Enter"){e.preventDefault(); addPhone();}}}
              data-testid="qs-phone-input"
            />
            <Btn type="button" onClick={addPhone} data-testid="qs-add-phone"><Plus className="h-4 w-4"/>Add</Btn>
          </div>

          {recipients.length > 0 ? (
            <div className="mt-4">
              <div className="label-overline mb-2">{recipients.length} number{recipients.length===1?"":"s"} added</div>
              <div className="flex max-h-48 flex-wrap gap-2 overflow-y-auto rounded-sm border border-zinc-900 bg-zinc-950 p-3">
                {recipients.map(p => (
                  <span key={p} className="inline-flex items-center gap-1.5 border border-zinc-800 bg-zinc-900 px-2 py-1 font-mono text-[11px]">
                    {p}
                    <button onClick={()=>removePhone(p)} className="text-zinc-500 hover:text-red-400" data-testid={`rm-${p}`}>✕</button>
                  </span>
                ))}
              </div>
            </div>
          ) : (
            <div className="mt-4 text-sm text-zinc-500">No numbers yet. Add your first one above.</div>
          )}

          {recipients.length > 0 && (
            <div className="mt-6 rounded-sm border border-zinc-900 bg-zinc-950 p-4">
              <div className="font-display text-sm">Would you like to add more people, {firstName}?</div>
              <div className="mt-2 text-xs text-zinc-500">You can keep going, or move on to write your message.</div>
            </div>
          )}

          <div className="mt-8 flex justify-between">
            <Btn variant="secondary" onClick={back} data-testid="qs-back-2"><ArrowLeft className="h-4 w-4"/>Back</Btn>
            <Btn onClick={next} data-testid="qs-next-2">Write the message <ArrowRight className="h-4 w-4"/></Btn>
          </div>
        </Card>
      )}

      {/* STEP 3: Message */}
      {step === 3 && (
        <Card testid="step-3">
          <div className="label-overline">Step 3 · The message</div>
          <h2 className="mt-2 font-display text-2xl font-semibold tracking-tight">
            What do you want to say?
          </h2>
          <p className="mt-1 text-sm text-zinc-500">Keep it short and warm. One segment = 160 characters.</p>

          <div className="mt-6 grid gap-4 lg:grid-cols-[1fr,320px]">
            <div>
              <TextArea
                value={message}
                onChange={(e)=>setMessage(e.target.value)}
                placeholder={`Hi there! This is ${user?.business_name || user?.name}. …`}
                className="min-h-[180px]"
                data-testid="qs-message"
              />
              <div className="mt-2 font-mono text-[11px] text-zinc-500">
                {message.length} chars · {segs} segment{segs===1?"":"s"} · {rate * segs} credits / recipient
              </div>
            </div>
            <div className="border border-zinc-800 bg-zinc-950 p-4">
              <div className="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Preview</div>
              <div className="mt-1 font-mono text-[10px] uppercase tracking-widest text-zinc-500">FROM {senderId || "—"}</div>
              <div className="mt-3 whitespace-pre-wrap break-words text-sm">{message || <span className="text-zinc-600">Your message will appear here.</span>}</div>
            </div>
          </div>

          <div className="mt-8 flex justify-between">
            <Btn variant="secondary" onClick={back} data-testid="qs-back-3"><ArrowLeft className="h-4 w-4"/>Back</Btn>
            <Btn onClick={next} data-testid="qs-next-3">Review <ArrowRight className="h-4 w-4"/></Btn>
          </div>
        </Card>
      )}

      {/* STEP 4: Review */}
      {step === 4 && (
        <Card testid="step-4">
          <div className="label-overline">Step 4 · Review</div>
          <h2 className="mt-2 font-display text-2xl font-semibold tracking-tight">
            Ready, {firstName}?
          </h2>
          <p className="mt-1 text-sm text-zinc-500">Here's the plan. Tap Send when it looks right.</p>

          <div className="mt-6 grid gap-4 lg:grid-cols-[1.2fr,1fr]">
            <div className="space-y-3 border border-zinc-900 bg-zinc-950 p-5">
              <div className="flex justify-between text-sm"><span className="text-zinc-500">Channel</span><Pill status={channel==="sms"?"info":"approved"}>{channel.toUpperCase()}</Pill></div>
              <div className="flex justify-between text-sm"><span className="text-zinc-500">From</span><span className="font-mono text-white">{senderId}</span></div>
              <div className="flex justify-between text-sm"><span className="text-zinc-500">Recipients</span><span className="font-mono text-white">{recipients.length}</span></div>
              <div className="flex justify-between text-sm"><span className="text-zinc-500">Segments / msg</span><span className="font-mono text-white">{segs}</span></div>
              <div className="border-t border-zinc-900 pt-3 flex items-center justify-between">
                <span className="label-overline">Total cost</span>
                <span className="font-mono text-2xl font-medium text-emerald-400">{creditsShort(totalCredits)} cr</span>
              </div>
              <div className="text-[11px] text-zinc-500">You'll have {creditsShort((wallet?.balance||0) - totalCredits)} credits left after sending.</div>
            </div>
            <div className="border border-zinc-800 bg-zinc-950 p-5">
              <div className="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Preview</div>
              <div className="mt-2 font-mono text-[10px] uppercase tracking-widest text-zinc-500">FROM {senderId}</div>
              <div className="mt-3 whitespace-pre-wrap break-words text-sm">{message}</div>
              <div className="mt-4 border-t border-zinc-900 pt-3 font-mono text-[10px] text-zinc-500">Goes to {recipients.length} number{recipients.length===1?"":"s"}</div>
            </div>
          </div>

          <div className="mt-8 flex justify-between">
            <Btn variant="secondary" onClick={back} data-testid="qs-back-4"><ArrowLeft className="h-4 w-4"/>Edit</Btn>
            <Btn onClick={dispatch} disabled={busy} data-testid="qs-dispatch">
              <Send className="h-4 w-4"/>{busy?"Sending…":`Send to ${recipients.length} now`}
            </Btn>
          </div>
        </Card>
      )}

      {/* STEP 5: Done */}
      {step === 5 && done && (
        <Card testid="step-5" className="text-center">
          <div className="mx-auto mt-2 grid h-14 w-14 place-items-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-emerald-400">
            <Check className="h-7 w-7"/>
          </div>
          <h2 className="mt-5 font-display text-3xl font-semibold tracking-tight">
            Dispatched, {done.name}.
          </h2>
          <p className="mt-2 text-sm text-zinc-400">
            {done.total} message{done.total===1?"":"s"} are on their way. {creditsShort(done.credits)} credits used.
          </p>
          <p className="mt-1 text-xs text-zinc-500">Delivery statuses will update as carriers confirm. Check the Campaigns page in a few seconds.</p>
          <div className="mt-8 flex justify-center gap-3">
            <Btn variant="secondary" onClick={startOver} data-testid="qs-again"><Sparkles className="h-4 w-4"/>Send another</Btn>
            <a href="/client/campaigns"><Btn data-testid="qs-campaigns">View campaign <ArrowRight className="h-4 w-4"/></Btn></a>
          </div>
        </Card>
      )}
    </div>
  );
}
