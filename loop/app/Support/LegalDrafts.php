<?php

namespace App\Support;

class LegalDrafts
{
    public const VERSION = '1.0';

    /**
     * @return array<string, array{title_en: string, title_sw: string, summary_en: string, summary_sw: string, body_en: string, body_sw: string}>
     */
    public static function all(): array
    {
        return [
            'terms' => self::terms(),
            'privacy' => self::privacy(),
            'business-terms' => self::business(),
            'affiliate-terms' => self::affiliate(),
            'rewards-terms' => self::rewards(),
            'payment-terms' => self::payment(),
            'acceptable-use' => self::acceptableUse(),
            'cookies' => self::cookies(),
            'data-rights' => self::dataRights(),
            'promotional-rules' => self::promotional(),
        ];
    }

    public static function render(string $body): string
    {
        $html = e($body);
        $html = preg_replace('/^## (.+)$/m', '<h2 class="mt-8 font-display text-xl font-semibold">$1</h2>', $html) ?? $html;
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html) ?? $html;

        return nl2br($html);
    }

    /**
     * @param  list<string>  $en
     * @param  list<string>  $sw
     * @return array{title_en: string, title_sw: string, summary_en: string, summary_sw: string, body_en: string, body_sw: string}
     */
    private static function pack(string $titleEn, string $titleSw, string $sumEn, string $sumSw, array $en, array $sw): array
    {
        return [
            'title_en' => $titleEn,
            'title_sw' => $titleSw,
            'summary_en' => $sumEn,
            'summary_sw' => $sumSw,
            'body_en' => implode("\n\n", $en),
            'body_sw' => implode("\n\n", $sw),
        ];
    }

    private static function bannerEn(): string
    {
        return "**Version 1.0 DRAFT — not lawyer-approved.** This document describes how Loop actually works. Tanzanian counsel must review it against the finished platform, the operating company, payment relationships, controller/processor roles, Games & Wins/raffles, and liability clauses before it is treated as production-final.";
    }

    private static function bannerSw(): string
    {
        return "**Toleo 1.0 RASIMU — halijaidhinishwa na wakili.** Hati hii inaeleza jinsi Loop inavyofanya kazi. Wakili wa Tanzania lazima aipitie kabla haijachukuliwa kuwa ya uzalishaji.";
    }

    /** @return array{title_en: string, title_sw: string, summary_en: string, summary_sw: string, body_en: string, body_sw: string} */
    private static function terms(): array
    {
        return self::pack(
            'Terms of Service',
            'Masharti ya Huduma',
            'Rules for using Loop as a member, business, staff member or affiliate.',
            'Sheria za kutumia Loop kama mwanachama, biashara, mfanyakazi au mshirika.',
            [
                self::bannerEn(),
                '**Effective date:** 1 September 2026. **Version:** 1.0.',
                '## 1. About Loop',
                'Loop is a technology platform that enables participating businesses to manage customer relationships, loyalty programmes, rewards, offers, campaigns, Front Desk sales recording, Discover, Content Studio, notifications, Games & Wins, raffles and related customer-engagement activities.',
                'These Terms govern access to and use of Loop by members, businesses, authorized staff, affiliates and other users.',
                'The operator of Loop is the legal entity named in Settings Hub → Legal & Compliance. Insert the full legal name, registration details, physical address and official contact information there. Tanzania\'s Electronic Transactions Act requires online suppliers to provide that identification.',
                '**Loop is the technology platform. Participating businesses remain responsible for the goods and services they sell to their customers.** Loyalty points recorded through Loop do not make Loop the seller of a burger, a haircut or a laundry service.',
                '## 2. Definitions',
                '**Member** means a customer who uses a phone number as their Loop identity. **Business** means a participating shop or brand. **Front Desk / Till** means authorized staff who record sales and redemptions. **Points** are loyalty units, not money. **Reward** is something a member has earned or unlocked. **Offer** is a promotion that may have separate eligibility. **Affiliate** is a person in the Loop Affiliate Programme.',
                '## 3. Your Loop account',
                'Users must provide accurate registration information and keep credentials, PINs and authentication methods secure. Accounts may not be sold, transferred, impersonated or used fraudulently. Loop may require reasonable verification to protect users, businesses or the platform.',
                'Member accounts are created with a phone number. A second member must not be created for the same phone number. Staff and owners remain responsible for anyone they authorize to use Front Desk.',
                '## 4. Businesses on Loop',
                'Participating businesses remain responsible for products and services they sell and for information they publish through Loop, including Content Studio materials. Unless Loop expressly states otherwise, Loop provides the technology enabling the relationship between the business and its customer.',
                'A participating business remains responsible for quality, availability, fulfilment, warranties, refunds and other obligations applicable to that business.',
                '## 5. Points, rewards, offers and sales',
                'Points, rewards, offers, sales records and redemptions are governed by the Rewards & Loyalty Programme Terms and the Payment & Subscription Terms. Front Desk records qualifying sales and processes redemptions inside the same Sale module. Users must not manufacture transactions, manipulate balances, or reverse genuine transactions for improper purposes. Loop may reverse benefits resulting from error, duplicate processing, fraud or unauthorized activity where appropriate and permitted.',
                '## 6. Discover, referrals and promotional campaigns',
                'Discover lists participating businesses using the Settings Hub sector catalogue. Scout/member invitations and affiliate referrals are governed by the applicable programme terms. Where Games & Wins or raffles are enabled, each promotion must follow the Promotional Games & Raffle Rules Framework and applicable law. Loop may suspend campaigns that appear unlawful, misleading, fraudulent or inconsistent with platform rules.',
                '## 7. Payments and subscriptions',
                'Where Loop accepts payment for Loop services, the payer must review the amount before confirming. A payment displayed as pending is not treated as completed until confirmation is received. Paid business features depend on the applicable subscription. Existing customer records and loyalty history are not automatically deleted merely because a subscription expires, subject to the retention policy.',
                '## 8. Acceptable use, IP and availability',
                'Users must not commit fraud, impersonate others, gain unauthorized access, introduce malware, scrape or attack the platform, manipulate points, publish unlawful material, or circumvent access controls. Loop\'s software, interfaces and branding remain Loop\'s (or its licensors\') property. Businesses retain rights in their own logos and content and grant Loop the permissions reasonably required to display and process them. Loop aims to provide reliable service but cannot guarantee uninterrupted availability.',
                '## 9. Suspension, liability, changes and law',
                'Loop may restrict or suspend accounts where reasonably necessary because of fraud, security risk, serious breach, legal requirement or misuse. Where appropriate, users should receive notice, subject to security and fraud-prevention requirements.',
                'Liability and indemnity wording must be reviewed by counsel. These drafts limit Loop\'s liability to the extent permitted by applicable law and do **not** attempt to waive statutory consumer or privacy rights that cannot legally be excluded. Business users should be responsible, to the extent permitted by law, for claims arising from their unlawful content, promotions, products, misuse of customer information or material breach.',
                'Loop may update these Terms as the platform evolves. Material changes should be communicated, and where fresh acceptance is required, Loop will obtain it. These Terms are governed by the laws of the United Republic of Tanzania, subject to mandatory rights.',
                '## 10. Contact',
                'Use the legal identity, legal email, privacy/DPO email and support contact published in Settings Hub → Legal & Compliance.',
            ],
            [
                self::bannerSw(),
                '**Tarehe ya kuanza:** 1 Septemba 2026. **Toleo:** 1.0.',
                '## 1. Kuhusu Loop',
                'Loop ni jukwaa la teknolojia linalowezesha biashara kushughulikia wateja, pointi, zawadi, ofa, kampeni, mauzo ya Kaunta, Discover, Content Studio, arifa, Michezo na Ushindi, bahati nasibu na shughuli zinazohusiana.',
                '**Loop ni jukwaa la teknolojia. Biashara zinazoshiriki zinabaki kuwajibika kwa bidhaa na huduma wanazouza.**',
                '## 2. Akaunti yako',
                'Watumiaji wanawajibika kutoa taarifa sahihi na kulinda PIN na njia za kuingia. Akaunti haziwezi kuuzwa, kuhamishwa au kutumika kwa udanganyifu. Mwanachama wa pili hataundwa kwa namba ileile ya simu.',
                '## 3. Pointi, zawadi na mauzo',
                'Pointi si pesa. Mauzo na ukombozi hufanyika katika moduli ileile ya Sale. Loop inaweza kurekebisha pointi zilizotokana na kosa, udanganyifu au shughuli isiyoruhusiwa.',
                '## 4. Malipo na usajili',
                'Malipo yanayoonyeshwa kama yanasubiri hayachukuliwi kuwa yamekamilika hadi uthibitisho upokee. Historia ya wateja haifutwi kiotomatiki kwa sababu tu usajili umekwisha.',
                '## 5. Sheria na mawasiliano',
                'Masharti haya yanaongozwa na sheria za Jamhuri ya Muungano wa Tanzania. Mawasiliano yako katika Settings Hub → Legal & Compliance.',
            ],
        );
    }

    /** @return array{title_en: string, title_sw: string, summary_en: string, summary_sw: string, body_en: string, body_sw: string} */
    private static function privacy(): array
    {
        return self::pack(
            'Privacy Policy',
            'Sera ya Faragha',
            'How Loop collects, uses and protects personal information.',
            'Jinsi Loop inavyokusanya, kutumia na kulinda taarifa binafsi.',
            [
                self::bannerEn(),
                '**Effective:** 1 September 2026. **Version:** 1.0.',
                '## Our commitment',
                'Loop respects the privacy of members, businesses, staff, affiliates and visitors. We process personal data in accordance with applicable data-protection requirements, including Tanzania\'s Personal Data Protection Act and applicable regulations.',
                'The Personal Data Protection Commission (PDPC) identifies rights including being informed, access, rectification, erasure/destruction, restriction, portability, objection, automated-decision protections, complaints, withdrawal of consent and compensation. Loop provides a Data Protection & Your Rights page and an in-account privacy-request workflow so those rights can be exercised.',
                '## Information we collect',
                'Depending on how someone uses Loop, this may include:',
                '**Identity and account information** — name, telephone number, email where supplied, account identifiers, PIN/authentication information in protected form, country and related profile information.',
                '**Profile information** — birthday, gender, city and interests where provided or required by the relevant experience.',
                '**Loyalty information** — participating businesses, points earned, adjustments, reward eligibility, rewards redeemed and loyalty history.',
                '**Transaction information** — sales attributed to a member, amounts, dates, locations/branches and associated loyalty events.',
                '**Business information** — business identity, contacts, sector, branches, staff, subscription information, campaigns, offers, rewards and platform configuration.',
                '**Affiliate information** — referral codes, attributed referrals, qualification activity, commissions and payout information where applicable.',
                '**Technical/security information** — device/browser information, logs, IP/network information where collected, authentication events and security/audit information.',
                '**Communications and preferences** — notification preferences, support communications and marketing permissions. Marketing consent is collected separately from Terms acceptance and is off by default.',
                '## Why Loop uses information',
                'Loop may process information to: create and secure accounts; identify members at participating businesses (including via the member Loop QR, which encodes a Till Sale lookup of the member\'s phone); calculate and maintain points; determine reward eligibility; process redemptions; provide personalized account experiences; operate campaigns and offers; provide Discover; manage subscriptions; process payments; operate affiliate/referral programmes; send operational notifications; prevent fraud and abuse; provide support; maintain platform security; meet legal obligations; and improve Loop based on legitimate and appropriately governed data use.',
                '## Businesses and customer information',
                'Controller/processor allocation depends on actual data flows and must be confirmed by counsel. Loop should not casually state that every business is the controller and Loop is merely a processor if Loop independently determines purposes for cross-business member identity, Discover, fraud prevention or platform analytics. That relationship will be documented accurately after review.',
                '## Sharing information',
                'Personal information may be shared where necessary with: participating businesses with whom the member interacts; authorized business staff; payment processors actually used by Loop; communications/SMS providers actually used by Loop; hosting/infrastructure/security providers; professional advisers; regulators/law-enforcement authorities where legally required; and other processors necessary to operate Loop.',
                '**Loop does not sell personal data.** This policy does not name a vendor unless Loop actually uses that vendor.',
                '## Cross-border processing',
                'If hosting, communications or other processors cause data to leave Tanzania, Loop must address applicable transborder-data requirements. A sentence saying “by using Loop you consent to international transfers” is not a substitute for regulatory compliance. Before production, Loop will map where the database, backups, logs, SMS, email, analytics, CDN and support tools live.',
                '## Retention',
                'Loop retains personal data only for as long as reasonably necessary for the purposes for which it was collected, including account operation, transaction integrity, fraud prevention, dispute resolution and legal/regulatory requirements. Loop does not keep data “forever.” When a business subscription expires, capabilities may pause; member history and balances are preserved according to retention/programme rules rather than being arbitrarily destroyed.',
                '## Security',
                'Loop uses reasonable organizational and technical safeguards including access controls, authentication, encryption where appropriate, audit controls, backups, monitoring and staff access restrictions. Loop does not promise that information can never be breached.',
                '## Your rights and complaints',
                'Use **Account → Legal & Documents → Your privacy rights** or **Make a privacy request** to access, correct, download, request erasure, restrict, object, withdraw consent, manage marketing preferences or complain. Users may also complain to the Personal Data Protection Commission. Contact the privacy/DPO email published in Settings Hub.',
            ],
            [
                self::bannerSw(),
                '**Tarehe:** 1 Septemba 2026. **Toleo:** 1.0.',
                '## Ahadi yetu',
                'Loop inaheshimu faragha ya wanachama, biashara, wafanyakazi, washirika na wageni, kwa mujibu wa Sheria ya Ulinzi wa Data Binafsi ya Tanzania.',
                '## Taarifa tunazokusanya',
                'Jina, namba ya simu, barua pepe inapowasilishwa, siku ya kuzaliwa, jinsia, mji, mapendeleo, uanachama, pointi, zawadi, mauzo, taarifa za biashara, rufaa, malipo, kumbukumbu za usalama, na idhini za masoko. Idhini ya masoko hukusanywa kando na Masharti na huwa imezimwa kwa chaguo-msingi.',
                '## Kwa nini tunatumia taarifa',
                'Kuunda akaunti, kutambua mwanachama kwenye Kaunta (ikiwa ni pamoja na QR ya Loop), kuhesabu pointi, kukomboa zawadi, Discover, usajili, malipo, rufaa, arifa, kuzuia udanganyifu, na kutimiza wajibu wa kisheria.',
                '## Kushiriki na uhifadhi',
                '**Loop haiuzi data binafsi.** Tunahifadhi data kwa muda unaohitajika tu. Historia ya mwanachama haifutwi kiotomatiki kwa sababu usajili wa biashara umesimama.',
                '## Haki zako',
                'Tumia Akaunti → Nyaraka za Kisheria → Haki zako za faragha, au wasiliana na PDPC.',
            ],
        );
    }

    /** @return array{title_en: string, title_sw: string, summary_en: string, summary_sw: string, body_en: string, body_sw: string} */
    private static function business(): array
    {
        return self::pack(
            'Business Terms',
            'Masharti ya Biashara',
            'Contractual rules for participating businesses.',
            'Sheria za kimkataba kwa biashara zinazoshiriki.',
            [
                self::bannerEn(),
                'This is a B2B agreement, not a duplicate of the general Terms. The business: is authorized to operate; is responsible for its products/services; provides accurate information; controls authorized staff access; must not fabricate sales or manipulate customer points; honours legitimately issued rewards according to published conditions; complies with applicable consumer, advertising and promotional requirements; only uploads content it has rights to use; does not misuse customer information or spam customers unlawfully; keeps credentials secure; pays applicable Loop subscription fees; is responsible for its own taxes unless specifically stated otherwise; reports suspected unauthorized access; and cooperates with legitimate fraud investigations.',
                'If a business fraudulently creates transactions, refuses legitimate redemptions, abuses members, runs prohibited promotions, misuses data or breaches the agreement, Loop may investigate, restrict relevant capability, suspend or terminate where justified, with notice/cure where circumstances allow.',
                'Staff permissions, Till audit logs and transaction records are evidence. The business remains responsible for managing authorized staff while Loop retains appropriate fraud/security controls.',
                'Loop provides loyalty/customer-engagement technology. The participating business remains responsible for goods and services sold to its customers.',
            ],
            [
                self::bannerSw(),
                'Hii ni makubaliano ya biashara kwa biashara. Biashara inawajibika kwa bidhaa/huduma, wafanyakazi, mauzo halali, zawadi, maudhui, faragha ya wateja, ada za usajili na kodi zake. Loop inaweza kuchunguza, kuzuia, kusimamisha au kusitisha pale inapohalalika.',
            ],
        );
    }

    /** @return array{title_en: string, title_sw: string, summary_en: string, summary_sw: string, body_en: string, body_sw: string} */
    private static function affiliate(): array
    {
        return self::pack(
            'Affiliate Programme Terms',
            'Masharti ya Programu ya Washirika',
            'Eligibility, attribution, commissions, fraud and termination.',
            'Ustahiki, uwiano, kamisheni, udanganyifu na kusitisha.',
            [
                self::bannerEn(),
                'Covers eligibility; how referral codes/links work; attribution windows; existing-customer rules; multiple-affiliate conflicts; when a referral becomes commissionable; and the commission basis.',
                '**Commission basis = plan at qualification.** A later upgrade does not silently recalculate the historical commission unless programme policy explicitly says otherwise.',
                'Prohibited: self-referral, fake businesses, fake accounts, fabricated transactions, duplicate accounts, misleading advertising, pretending to be Loop, unauthorized paid advertising using protected branding where prohibited, commission manipulation and collusion.',
                'Also covers pending commissions, approved commissions, reversals, minimum payout, payment schedule, taxes, disputed attribution, suspension, termination and outstanding legitimate commission treatment.',
            ],
            [
                self::bannerSw(),
                '**Msingi wa kamisheni = mpango wakati wa kufuzu.** Kuboresha baadaye hakubadilishi kamisheni ya historia isipokuwa sera inasema wazi. Kujirejelea, biashara bandia, akaunti bandia na udanganyifu wa kamisheni ni marufuku.',
            ],
        );
    }

    /** @return array{title_en: string, title_sw: string, summary_en: string, summary_sw: string, body_en: string, body_sw: string} */
    private static function rewards(): array
    {
        return self::pack(
            'Rewards & Loyalty Programme Terms',
            'Masharti ya Pointi na Zawadi',
            'How points, rewards, earning, redemption, reversals and disputes work.',
            'Jinsi pointi, zawadi, kupata, kukomboa, kurekebisha na migogoro inavyofanya kazi.',
            [
                self::bannerEn(),
                'Points are loyalty units. Unless expressly structured otherwise they are not money, not deposits, not interest-bearing, not ordinarily transferable, and not ordinarily withdrawable as cash.',
                'Points arise only from valid qualifying activity. If a sale is cancelled, refunded, reversed or entered incorrectly, corresponding loyalty entries may be adjusted. Fraudulently generated points may be reversed.',
                'Reward states include unlocked, ready to redeem, redeemed, expired where applicable, and reversed/cancelled where legitimately permitted, plus business-specific conditions. An offer is not necessarily an earned reward.',
                'If a participating business\'s Loop subscription becomes paused: member history and balances are preserved according to Loop\'s retention/programme rules; active earning/redemption capabilities may be paused until the business reactivates. Members are not told their points have vanished.',
                'Redemption remains tied to the sale: Front Desk handles Customer → Sale → available reward → redeem → complete transaction in the same Sale module.',
            ],
            [
                self::bannerSw(),
                'Pointi si pesa, si amana, na haziondolewi kama fedha. Zinatokana na shughuli halali. Ukiacha usajili, historia na salio vinahifadhiwa; uwezo wa kupata/kukomboa unaweza kusimamishwa. Ukombozi unaendelea kwenye Sale ileile.',
            ],
        );
    }

    /** @return array{title_en: string, title_sw: string, summary_en: string, summary_sw: string, body_en: string, body_sw: string} */
    private static function payment(): array
    {
        return self::pack(
            'Payment & Subscription Terms',
            'Masharti ya Malipo na Usajili',
            'Packages, billing, renewals, failures, refunds and suspension.',
            'Kifurushi, bili, kuhuisha, kushindwa, kurejesha na kusimamisha.',
            [
                self::bannerEn(),
                'Covers packages and features, monthly pricing, multi-month purchases, discounts, renewal, upgrades, downgrades, effective dates, taxes where applicable, payment providers actually used, failed payments, pending payments, duplicate payments, refunds, erroneous charges, invoices/receipts, payment disputes, suspension, reactivation, plan changes, discontinued plans, price changes and notice of material changes.',
                'A payment-service-provider request being **pending** does not mean Loop has received payment. Loop will not treat a pending state as a successful subscription. These legal terms match Loop\'s single-payment-surface architecture.',
                'When a subscription expires, Loop may pause paid capabilities. Existing customer records, loyalty history and other retained business data are not automatically deleted merely because a subscription expires, subject to retention policy and applicable law. Renewal restores eligible functionality according to the current subscription terms.',
            ],
            [
                self::bannerSw(),
                'Ombi la malipo linaloonyeshwa **linasubiri** halimaanishi Loop imepokea malipo. Uwezo unaolipiwa unaweza kusimamishwa; data ya wateja haifutwi kiotomatiki.',
            ],
        );
    }

    /** @return array{title_en: string, title_sw: string, summary_en: string, summary_sw: string, body_en: string, body_sw: string} */
    private static function acceptableUse(): array
    {
        return self::pack(
            'Acceptable Use Policy',
            'Sera ya Matumizi Yanayokubalika',
            'What is and is not permitted on Loop.',
            'Kilichoruhusiwa na kisichoruhusiwa kwenye Loop.',
            [
                self::bannerEn(),
                'Prohibited: hacking; unauthorized access; malware; scraping contrary to authorization; automated abuse; credential sharing contrary to permissions; impersonation; fraudulent transactions; point farming; fake referrals; fake businesses; reward manipulation; abusive or unlawful content; misleading promotions; infringement; privacy violations; harassment; attempts to circumvent limits; interference with platform availability; and using Loop customer information to unlawfully spam customers.',
                'Enforcement may include warning, restriction, investigation, suspension, termination, preservation of evidence and lawful reporting where required.',
            ],
            [
                self::bannerSw(),
                'Marufuku: uvunjaji, ufikiaji usioruhusiwa, programu hasidi, udanganyifu wa pointi/rufaa, maudhui haramu, na kutumia taarifa za wateja kutuma spam kinyume cha sheria. Hatua: onyo, kuzuia, uchunguzi, kusimamisha, kusitisha.',
            ],
        );
    }

    /** @return array{title_en: string, title_sw: string, summary_en: string, summary_sw: string, body_en: string, body_sw: string} */
    private static function cookies(): array
    {
        return self::pack(
            'Cookie Policy',
            'Sera ya Vidakuzi',
            'How cookies and similar technologies are used.',
            'Jinsi vidakuzi na teknolojia zinazofanana zinavyotumika.',
            [
                self::bannerEn(),
                'This policy follows Loop\'s actual implementation, not imagination. Loop currently uses:',
                '**Strictly necessary** — login/session cookie, CSRF/XSRF token, language (locale) preference, and core account functionality. These are required for the service to work.',
                '**Preferences** — remembering appropriate user choices such as locale and preferred country.',
                '**Analytics** — only if actually enabled. Loop does not currently ship a third-party marketing analytics pixel in the core product. If that changes, this policy must be updated before use.',
                '**Marketing** — only if actually used and legally appropriate. Marketing consent remains separate from Terms.',
                'Session cookies last for the session (or the configured session lifetime). Preference values may persist in the session or a remember token where the user chooses to stay signed in.',
            ],
            [
                self::bannerSw(),
                'Loop hutumia vidakuzi vya kikao, tokeni ya CSRF, lugha na utendaji wa akaunti. Analytics/marketing hutajwa tu ikiwa Loop inavitumia kweli.',
            ],
        );
    }

    /** @return array{title_en: string, title_sw: string, summary_en: string, summary_sw: string, body_en: string, body_sw: string} */
    private static function dataRights(): array
    {
        return self::pack(
            'Data Protection & Your Rights',
            'Ulinzi wa Data na Haki Zako',
            'How to exercise privacy rights on Loop.',
            'Jinsi ya kutumia haki za faragha kwenye Loop.',
            [
                self::bannerEn(),
                'From Account → Legal & Documents you can: access your information; correct your information; download your information; request deletion/erasure; restrict processing; object to certain processing; withdraw consent; manage marketing preferences; and make a privacy complaint.',
                '**Make a request** creates a Privacy Operations case in Admin. Loop will review the request according to applicable law, including where retention, fraud prevention or legal obligations require information to be kept for a period.',
                'The PDPC has authority to receive and investigate complaints relating to alleged violations of personal-data protections.',
            ],
            [
                self::bannerSw(),
                'Kutoka Akaunti unaweza kufikia, kurekebisha, kupakua, kuomba kufuta, kuzuia, kupinga, kuondoa idhini, na kulalamika. Ombi linaunda kesi katika Admin → Privacy.',
            ],
        );
    }

    /** @return array{title_en: string, title_sw: string, summary_en: string, summary_sw: string, body_en: string, body_sw: string} */
    private static function promotional(): array
    {
        return self::pack(
            'Promotional Games & Raffle Rules Framework',
            'Mfumo wa Michezo na Bahati Nasibu',
            'Framework governing each promotional campaign.',
            'Mfumo unaosimamia kila kampeni ya matangazo.',
            [
                self::bannerEn(),
                'This is a **master promotional framework**. Each game or raffle must generate its own Official Rules from its configuration. A business must not assume Loop\'s generic Terms legalize a promotion.',
                'Each promotion captures: promoter (the business responsible); promotion name; eligibility; start/end; territory; how to qualify (spend / visits / both); how to participate; play limit; prize inventory; number of prizes; winner mechanism; claim deadline; how winners are notified; unclaimed prize treatment; no-win possibility; disqualification/fraud; errors/interruption; complaints; promoter contact; and Loop\'s role as technology provider.',
                'Loop may reject or suspend non-compliant promotions. A contract cannot legalize an otherwise unlawful promotion. Games & Wins / raffles require separate Tanzania regulatory/legal review before being enabled commercially.',
            ],
            [
                self::bannerSw(),
                'Kila mchezo au bahati nasibu lazima uwe na Sheria zake rasmi. Loop inaweza kusimamisha matangazo yasiyotii. Michezo/bahati nasibu yanahitaji ukaguzi tofauti wa kisheria nchini Tanzania kabla ya uzinduzi wa kibiashara.',
            ],
        );
    }
}
