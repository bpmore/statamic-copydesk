let j=null;function ee(e){j||(j=document.createElement("div"),j.className="copydesk-sr-only",j.setAttribute("aria-live","polite"),j.setAttribute("role","status"),document.body.appendChild(j)),j.textContent="",setTimeout(()=>{j.textContent=e},50)}const te=/Mac|iPhone|iPad/.test(navigator.platform||navigator.userAgent),ne=te?"Option+Enter":"Alt+Enter",se=600;function z({target:e,kind:o,read:p,onResult:d}){let y=null,g=null,f=!1;async function i(){var _;y=null,g==null||g.abort(),g=new AbortController;const b=p(),C=Statamic.$app.config.globalProperties.$axios;try{const{data:v}=await C.post(cp_url("copydesk/lint"),{...e.request(),field:e.field,kind:o,value:b.value},{signal:g.signal});d(v,b)}catch(v){if((v==null?void 0:v.code)==="ERR_CANCELED"||(v==null?void 0:v.name)==="CanceledError")return;[401,403,404,419].includes((_=v==null?void 0:v.response)==null?void 0:_.status)&&k()}}function c(){f||(clearTimeout(y),y=setTimeout(i,se))}function k(){f=!0,clearTimeout(y),g==null||g.abort()}return{schedule:c,stop:k,now:i}}const{reactive:oe}=window.Vue,D=oe({fields:{}});function U(e,o){D.fields[e]=o}function G(e){delete D.fields[e]}const W={error:{word:"To fix",order:0},warning:{word:"To look at",order:1},tip:{word:"Tip",order:2},info:{word:"Tip",order:2}};function O(e){return W[e.severity]?e.severity:"warning"}function J(e){const o=e.trim();return o===""?"Remove it":"Use “"+o+"”"}let V=null;function re({anchor:e,issue:o,focus:p,onReplace:d,onIgnore:y,onReturn:g}){var L;R();const f=O(o),i=document.createElement("div"),c="copydesk-popover-"+Math.random().toString(36).slice(2);i.className="copydesk-popover",i.dataset.severity=f,i.setAttribute("role","dialog"),i.setAttribute("aria-labelledby",c);const k=document.createElement("p");k.className="copydesk-popover__kind",k.textContent=W[f].word;const b=document.createElement("p");b.className="copydesk-popover__message",b.id=c,b.textContent=o.message;const C=document.createElement("div");C.className="copydesk-popover__actions";const _=(l,r,h=!1)=>{const a=document.createElement("button");return a.type="button",a.className="copydesk-popover__button"+(h?" copydesk-popover__button--primary":""),a.textContent=l,a.addEventListener("click",()=>{R(),r()}),C.appendChild(a),a};for(const l of o.suggestions??[])_(J(l),()=>d(l),!0);_("Ignore here",y),_("Close",g),i.append(k,b,C),document.body.appendChild(i),Y(i,e()),i.addEventListener("keydown",l=>{l.key==="Escape"&&(l.preventDefault(),l.stopPropagation(),R(),g())}),i.addEventListener("focusout",l=>{v()&&!i.contains(l.relatedTarget)&&R()});const v=()=>(V==null?void 0:V.box)===i,P=l=>{v()&&!i.contains(l.target)&&R()},T=()=>{if(!v())return;const l=e();l?Y(i,l):R()};setTimeout(()=>v()&&document.addEventListener("mousedown",P,!0)),window.addEventListener("scroll",T,!0),V={box:i,issue:o,teardown(){document.removeEventListener("mousedown",P,!0),window.removeEventListener("scroll",T,!0)}},p&&((L=C.querySelector("button"))==null||L.focus())}function R(){if(!V)return;const{box:e,teardown:o}=V;V=null,o(),e.remove()}function q(){return(V==null?void 0:V.issue)??null}function Y(e,o){const y=e.offsetWidth,g=e.offsetHeight,f=o.bottom+6,i=f+g>window.innerHeight-8&&o.top-6-g>8?o.top-6-g:f;e.style.top=Math.round(i)+"px",e.style.left=Math.round(Math.max(8,Math.min(o.left,window.innerWidth-y-8)))+"px"}function ie(e,o){if(o.path==null||o.offset==null||o.length==null)return null;let p=e,d=0;for(const f of String(o.path).split(".")){if(!/^\d+$/.test(f))return null;const i=Number(f);if(i>=p.childCount)return null;for(let c=0;c<i;c++)d+=p.child(c).nodeSize;p=p.child(i),d+=1}if(!p.isTextblock)return null;const y=K(p,d,o.offset,1),g=K(p,d,o.offset+o.length,-1);return y===null||g===null||g<=y?null:{from:y,to:g}}function K(e,o,p,d){let y=0,g=o;for(let f=0;f<e.childCount;f++){const i=e.child(f),c=i.isText?i.text.length:i.type.name==="hardBreak"?1:0;if(c>0&&(p<y+c||d<0&&p===y+c))return g+(p-y);y+=c,g+=i.nodeSize}return p===y?g:null}function Q(e,o,p){const d=(e.match(/[ \t]+$/)??[""])[0].length,y=(o.match(/^[ \t]+/)??[""])[0].length;return p===""?{back:0,forward:d>0||e===""?y:0}:{back:/^[,.;:!?)\]]/.test(p)?d:0,forward:/[ \t]$/.test(p)?y:0}}const{unref:$,watch:le}=window.Vue,ae="readability_panel",B="copydesk_ignores";function H(e){var p;const o=(p=$(e==null?void 0:e.values))==null?void 0:p[B];return Array.isArray(o)?o.filter(d=>typeof d=="string"):[]}function ce(e,o){o&&e.setFieldValue(B,[...new Set([...H(e),o])])}function ue(e){e.setFieldValue(B,[])}function X(e,{handle:o,fieldPathPrefix:p,config:d},y){const g=Z($(e==null?void 0:e.blueprint));if(!g)return null;const f=(p||o||"").split("."),i=f.length>1;return!(g.prose_fields??[]).includes(f[0])||i&&y==="text"?null:{field:f[0],key:[p,o].filter(Boolean).join(".")||o,label:(d==null?void 0:d.display)||o,collection:g.collection??null,ignores:()=>H(e),ignore:c=>ce(e,c),onIgnoresChange:c=>le(()=>H(e).join(`
`),c),request:()=>({reference:$(e.reference)||null,site:$(e.site)||null,collection:g.collection??null})}}function Z(e){if(!e||typeof e!="object")return null;if(e.handle===ae)return e.config??e;for(const o of Object.values(e)){const p=Z(o);if(p)return p}return null}const de=e=>e.rule+"\0"+e.excerpt;function fe({bard:e,tiptap:o}){const p=X(e.publishContainer,e,"bard");if(!p)return[];const{Extension:d}=o.core,{Plugin:y,PluginKey:g,TextSelection:f}=o.pm.state,{Decoration:i,DecorationSet:c}=o.pm.view,k=new g("copydeskLint"),b=l=>new y({key:k,state:{init:()=>({set:c.empty,maps:[]}),apply(r,h){let{set:a,maps:n}=h;const s=r.getMeta(k);return r.docChanged&&(a=a.map(r.mapping,r.doc),n.push(r.mapping)),s!=null&&s.sent&&(n=[]),s!=null&&s.found&&(a=c.create(r.doc,s.found.map(({from:t,to:u,issue:m})=>i.inline(t,u,{class:"copydesk-issue copydesk-issue--"+O(m),"data-copydesk-rule":m.rule},{issue:m})))),s!=null&&s.remove&&(a=a.remove(a.find(void 0,void 0,t=>s.remove(t.issue)))),{set:a,maps:n}}},props:{decorations:r=>k.getState(r).set,handleClick(r,h){const a=C(r.state,h);return a&&v(r,a,!1),!1},handleKeyDown(r,h){if(!(h.altKey&&h.key==="Enter"))return!1;const a=C(r.state,r.state.selection.from);return a?(v(r,a,!0),!0):!1}},view(r){let h=null;const a=z({target:p,kind:"bard",read(){return r.dispatch(r.state.tr.setMeta(k,{sent:!0}).setMeta("addToHistory",!1)),{value:l.getJSON().content??[],doc:r.state.doc}},onResult(s,t){if(r.isDestroyed)return;const{maps:u}=k.getState(r.state),m=p.ignores(),x=[];for(const w of s.issues??[]){const E=m.includes(w.fingerprint)?null:ie(t.doc,w);if(!E)continue;const I=u.reduce((A,M)=>M.map(A,1),E.from),N=u.reduce((A,M)=>M.map(A,-1),E.to);I<N&&x.push({from:I,to:N,issue:w})}r.dispatch(r.state.tr.setMeta(k,{found:x}).setMeta("addToHistory",!1)),L(r)}});r.editable&&a.schedule();const n=p.onIgnoresChange(()=>a.schedule());return{update(s,t){!s.state.doc.eq(t.doc)&&s.editable&&a.schedule();const{selection:u}=s.state,m=u.empty?C(s.state,u.from):null,x=m?de(m.issue):null;x&&x!==h&&ee(W[O(m.issue)].word+": "+m.issue.message+" "+ne+" for choices."),h=x;const w=q();w&&!_(s.state,w)&&s.hasFocus()&&R()},destroy(){a.stop(),n(),G(p.key),_(r.state,q())&&R()}}}});function C(l,r){const h=k.getState(l).set.find(r,r).filter(n=>n.from<=r&&r<=n.to);if(!h.length)return null;const a=h.reduce((n,s)=>s.to-s.from<n.to-n.from?s:n);return{from:a.from,to:a.to,issue:a.spec.issue}}function _(l,r){if(!r)return null;const h=n=>n===r||n.rule===r.rule&&n.excerpt===r.excerpt&&n.path===r.path&&n.offset===r.offset,a=k.getState(l).set.find(void 0,void 0,n=>h(n.issue))[0];return a?{from:a.from,to:a.to,issue:r}:null}function v(l,r,h){re({anchor:()=>{const n=l.isDestroyed?null:_(l.state,r.issue);if(!n)return null;const s=l.coordsAtPos(n.from),t=l.coordsAtPos(n.to);return{left:s.left,top:Math.min(s.top,t.top),bottom:Math.max(s.bottom,t.bottom)}},issue:r.issue,focus:h,onReplace:n=>P(l,r.issue,n),onIgnore:()=>T(l,r.issue),onReturn:()=>l.focus()})}function P(l,r,h){const a=_(l.state,r);if(!a){l.focus();return}const{doc:n}=l.state,s=n.resolve(a.from),t=n.resolve(a.to),u=n.textBetween(s.start(),a.from,`
`,`
`),m=n.textBetween(a.to,t.end(),`
`,`
`),{back:x,forward:w}=Q(u,m,h),E=a.from-x,I=l.state.tr.insertText(h,E,a.to+w);I.setSelection(f.create(I.doc,E+h.length)),l.dispatch(I.scrollIntoView()),l.focus()}function T(l,r){p.ignore(r.fingerprint),l.dispatch(l.state.tr.setMeta(k,{remove:h=>h.fingerprint===r.fingerprint}).setMeta("addToHistory",!1)),l.focus(),L(l)}function L(l){const r=k.getState(l.state).set.find().sort((h,a)=>h.from-a.from).map(h=>({issue:h.spec.issue,reveal(){const a=_(l.state,h.spec.issue);return a?(l.dispatch(l.state.tr.setSelection(f.create(l.state.doc,a.from,a.to)).scrollIntoView()),l.focus(),requestAnimationFrame(()=>v(l,a,!1)),!0):!1}}));U(p.key,{label:p.label,issues:r})}return d.create({name:"copydeskLint",addProseMirrorPlugins(){return[b(this.editor)]}})}const{h:S,ref:pe,shallowRef:ge,defineComponent:me,onMounted:he,onUpdated:ye,onBeforeUnmount:ve}=window.Vue;function ke(e,o){return me({name:"CopydeskIssueList",inheritAttrs:!1,setup(p,{attrs:d,expose:y}){const g=pe(null);y(new Proxy({},{get:(n,s)=>{var t;return(t=g.value)==null?void 0:t[s]},has:(n,s)=>g.value!=null&&s in g.value}));let f=null;try{f=window.__STATAMIC__.ui.injectPublishContext()}catch{f=null}const i=X(f,{handle:d.handle,fieldPathPrefix:d.fieldPathPrefix,config:d.config},o);if(!i)return()=>S(e,{...d,ref:g});const c=ge({issues:[],value:null});let k=d.value;const b=z({target:i,kind:o,read:()=>({value:typeof d.value=="string"?d.value:""}),onResult(n,s){c.value={issues:n.issues??[],value:s.value},h()}});he(()=>b.schedule()),ye(()=>{d.value!==k&&(k=d.value,b.schedule())});const C=i.onIgnoresChange(()=>h());ve(()=>{b.stop(),C(),G(i.key)});const _=()=>{const n=i.ignores();return c.value.issues.filter(s=>!n.includes(s.fingerprint))},v=n=>n.offset!=null&&o!=="markdown",P=()=>d.id?document.getElementById(d.id):null,T=n=>n&&HTMLElement.prototype.focus.call(n);function L(n,s){var A;const t=typeof d.value=="string"?d.value:"";if(t!==c.value.value){b.now();return}const u=t.replace(/\r\n?/g,`
`),m=n.offset,x=n.offset+n.length,{back:w,forward:E}=Q(u.slice(0,m),u.slice(x),s),I=u.slice(0,m-w)+s+u.slice(x+E),N=m-w+s.length;(A=d["onUpdate:value"])==null||A.call(d,I),c.value={issues:[],value:null},requestAnimationFrame(()=>{var F;const M=P();T(M),(F=M==null?void 0:M.setSelectionRange)==null||F.call(M,N,N)})}function l(n){i.ignore(n.fingerprint),h()}function r(n){var t,u;const s=P();return s&&v(n)?(T(s),(t=s.setSelectionRange)==null||t.call(s,n.offset,n.offset+n.length)):((u=document.querySelector('[data-copydesk-list="'+CSS.escape(i.key)+'"]'))==null||u.scrollIntoView({block:"center"}),T(s)),!0}function h(){U(i.key,{label:i.label,issues:_().map(n=>({issue:n,reveal:()=>r(n)}))})}function a(){const n=_();return n.length?S("div",{class:"copydesk-field-issues","data-copydesk-list":i.key},[S("p",{class:"copydesk-sr-only"},n.length===1?"Copydesk found 1 thing in this field.":"Copydesk found "+n.length+" things in this field."),S("ul",{class:"copydesk-field-issues__list"},n.map((s,t)=>{const u=O(s);return S("li",{key:t,class:"copydesk-field-issue","data-severity":u},[S("span",{class:"copydesk-field-issue__kind"},W[u].word)," ",S("span",{class:"copydesk-field-issue__message"},s.message),s.excerpt?[" ",S("mark",{class:"copydesk-field-issue__excerpt"},s.excerpt)]:null,S("span",{class:"copydesk-field-issue__actions"},[...v(s)?(s.suggestions??[]).map(m=>S("button",{type:"button",class:"copydesk-popover__button copydesk-popover__button--primary",onClick:()=>L(s,m)},J(m))):(s.suggestions??[]).length?[S("span",{class:"copydesk-field-issue__try"},"Try: "+s.suggestions.map(m=>m.trim()||"leaving it out").join(", "))]:[],S("button",{type:"button",class:"copydesk-popover__button",onClick:()=>l(s)},"Ignore here")])])}))]):null}return()=>[S(e,{...d,ref:g}),a()]}})}Statamic.$bard.addExtension(fe);Statamic.configuring(()=>{const e=Statamic.$app;for(const[o,p]of[["markdown-fieldtype","markdown"],["textarea-fieldtype","textarea"],["text-fieldtype","text"]]){const d=e.component(o);d&&e.component(o,ke(d,p))}});(function(){const{injectPublishContext:e}=window.__STATAMIC__.ui,o=[{key:"error",color:"red",one:"1 to fix",many:f=>f+" to fix"},{key:"warning",color:"amber",one:"1 to look at",many:f=>f+" to look at"},{key:"tip",color:"gray",one:"1 tip",many:f=>f+" tips"}],p={on_target:{color:"emerald",words:"on target"},above:{color:"red",words:"harder than your target"},below:{color:"emerald",words:"easier than your target, which is fine"}};function d(f){return String(f).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;")}const y=70;function g(f){if(!f)return"";const i=Array.from(f.text);let c=i.slice(0,f.offset).join("");const k=i.slice(f.offset,f.offset+f.length).join("");let b=i.slice(f.offset+f.length).join("");return i.length>2*y&&f.length<i.length&&(c.length>y&&(c="…"+c.slice(-y).replace(/^\S*\s/,"")),b.length>y&&(b=b.slice(0,y).replace(/\s\S*$/,"")+"…")),d(c)+"<mark>"+d(k)+"</mark>"+d(b)}Statamic.$components.register("readability_panel-fieldtype",{props:["config","meta"],setup(f){const i=e(),c=Vue.reactive({checking:!1,result:null,failed:null,notYet:!1});async function k(){var u,m,x,w,E,I,N;c.checking=!0,c.failed=null,c.notYet=!1;const t=Statamic.$app.config.globalProperties.$axios;try{const{data:A}=await t.post(cp_url("copydesk/check"),{reference:(u=i.reference)==null?void 0:u.value,values:i.values.value,site:(m=i.site)==null?void 0:m.value,collection:(x=f.config)==null?void 0:x.collection,blueprint:(w=f.config)==null?void 0:w.blueprint});c.result=A}catch(A){c.notYet=((E=A.response)==null?void 0:E.status)===422,c.failed=((N=(I=A.response)==null?void 0:I.data)==null?void 0:N.message)||"The check could not be run.",c.result=null}finally{c.checking=!1}}const b=Vue.computed(()=>{var u;const t=(((u=i.errors)==null?void 0:u.value)??{}).copydesk;return Array.isArray(t)?t:[]});Vue.watch(b,t=>{t.length>0&&(c.result=null,c.failed=null)});const C=Vue.computed(()=>b.value.length>0&&!c.result&&!c.failed&&!c.checking),_=Vue.computed(()=>{const t=c.result;if(!t||!t.band)return null;const u=p[t.comparison]??p.on_target;return{color:u.color,text:"Your target is "+t.target.label+". This page reads "+u.words+"."}}),v=Vue.computed(()=>{var u;const t=((u=c.result)==null?void 0:u.findings)??[];return o.map(m=>{const x=t.filter(w=>w.severity===m.key);return{key:m.key,color:m.color,items:x,badge:x.length===1?m.one:m.many(x.length)}}).filter(m=>m.items.length>0)}),P=Vue.computed(()=>{var t;return(((t=c.result)==null?void 0:t.sections)??[]).map(u=>{var m;return{...u,name:u.lead?"Before the first heading":u.title,color:u.band?((m=p[u.comparison])==null?void 0:m.color)??"gray":"gray",label:u.band?u.band.label:"Nothing to grade"}})});function T(t){return t.slice(0,5)}const L=Vue.computed(()=>Object.values(D.fields).flatMap(t=>t.issues.map(u=>({...u,label:t.label}))).sort((t,u)=>W[O(t.issue)].order-W[O(u.issue)].order)),l=Vue.computed(()=>o.map(t=>{const u=L.value.filter(m=>O(m.issue)===t.key||t.key==="tip"&&m.issue.severity==="info").length;return{key:t.key,color:t.color,n:u,badge:u===1?t.one:t.many(u)}}).filter(t=>t.n>0)),r=20,h=Vue.computed(()=>H(i).length);function a(t){return t===1?"1 thing is ignored on this page.":t+" things are ignored on this page."}function n(){ue(i)}function s(t){return"Show "+(t.issue.excerpt?"“"+t.issue.excerpt+"”":"it")+" in "+t.label}return{state:c,check:k,verdict:_,groups:v,sections:P,highlighted:g,shown:T,refusal:b,showRefusal:C,liveItems:L,liveCounts:l,LIVE_SHOWN:r,jumpLabel:s,ignoredCount:h,ignoredWords:a,stopIgnoring:n,severityWord:t=>W[O(t)].word}},template:`
            <div class="space-y-3">
                <div v-if="liveItems.length || ignoredCount" class="space-y-2">
                    <ui-heading size="sm" text="As you type" />
                    <div class="flex flex-wrap gap-2">
                        <div v-for="c in liveCounts" :key="c.key"><ui-badge :color="c.color" :text="c.badge" /></div>
                    </div>
                    <ul class="space-y-1">
                        <li v-for="(item, i) in liveItems.slice(0, LIVE_SHOWN)" :key="'l' + i" class="text-xs">
                            <button type="button" class="copydesk-jump" @click="item.reveal()">{{ jumpLabel(item) }}</button>
                            <span>{{ ': ' + severityWord(item.issue) + '. ' + item.issue.message }}</span>
                        </li>
                    </ul>
                    <p v-if="liveItems.length > LIVE_SHOWN" class="text-xs">{{ 'And ' + (liveItems.length - LIVE_SHOWN) + ' more.' }}</p>
                    <p v-if="ignoredCount" class="text-xs">
                        {{ ignoredWords(ignoredCount) + ' ' }}
                        <button type="button" class="copydesk-jump" @click="stopIgnoring">Stop ignoring</button>
                    </p>
                </div>

                <ui-alert
                    v-if="showRefusal"
                    variant="error"
                    :heading="refusal[0]"
                    :text="refusal.slice(1).join(' ')"
                />

                <ui-description
                    v-if="meta && meta.can === false"
                    text="Checking readability is not something your role can do."
                />

                <div v-else>
                    <ui-button
                        size="sm"
                        :text="state.checking ? 'Checking' : 'Check readability'"
                        :disabled="state.checking"
                        @click="check"
                    />
                </div>

                <ui-skeleton v-if="state.checking" class="h-16 w-full" />

                <ui-alert
                    v-else-if="state.failed"
                    :variant="state.notYet ? 'warning' : 'error'"
                    :text="state.failed"
                />

                <template v-else-if="state.result">
                    <ui-alert
                        v-if="!state.result.locale.allowed"
                        variant="warning"
                        :text="state.result.locale.message"
                    />

                    <template v-else>
                        <ui-description v-for="(notice, i) in state.result.notices" :key="'n' + i" :text="notice" />

                        <div v-if="state.result.band" class="space-y-2">
                            <div><ui-badge :color="verdict.color" :text="state.result.band.label" /></div>
                            <ui-description :text="verdict.text" />
                        </div>

                        <ui-description v-else-if="!state.result.notices.length" text="Nothing to grade yet: none of the fields this check reads has any text." />

                        <div v-if="sections.length > 1" class="space-y-2">
                            <ui-heading size="sm" text="By section" />
                            <div v-for="(s, i) in sections" :key="'s' + i" class="flex flex-wrap items-center gap-2">
                                <span class="text-sm">{{ s.name }}</span>
                                <ui-badge :color="s.color" :text="s.label" />
                                <span v-if="s.grades" class="text-xs">Flesch-Kincaid {{ s.grades.flesch_kincaid }} · reading ease {{ s.grades.reading_ease }}</span>
                                <span v-if="s.findings" class="text-xs">{{ s.findings === 1 ? '1 finding' : s.findings + ' findings' }}</span>
                            </div>
                        </div>

                        <div v-for="g in groups" :key="g.key" class="space-y-3">
                            <div><ui-badge :color="g.color" :text="g.badge" /></div>
                            <div v-for="(f, i) in g.items" :key="g.key + i" class="space-y-1">
                                <ui-description :text="f.message" />
                                <p v-if="f.context" class="text-xs" v-html="highlighted(f.context)"></p>
                                <p v-for="(span, j) in shown(f.spans)" :key="g.key + i + 's' + j" class="text-xs" v-html="highlighted(span.context)"></p>
                                <p v-if="f.spans.length > 5" class="text-xs">{{ 'And ' + (f.spans.length - 5) + ' more.' }}</p>
                            </div>
                        </div>

                        <div v-if="state.result.band && !groups.length"><ui-badge color="emerald" text="Nothing to fix" /></div>

                        <ui-description
                            v-if="state.result.summary && state.result.summary.ignored"
                            :text="state.result.summary.ignored === 1 ? '1 finding is ignored here, and not counted above.' : state.result.summary.ignored + ' findings are ignored here, and not counted above.'"
                        />

                        <details v-if="state.result.grades" class="text-xs">
                            <summary>The numbers behind the band</summary>
                            <p>{{ 'Flesch-Kincaid ' + state.result.grades.flesch_kincaid + ' · Gunning Fog ' + state.result.grades.gunning_fog + ' · SMOG ' + state.result.grades.smog + ' · Coleman-Liau ' + state.result.grades.coleman_liau }}</p>
                            <p>{{ 'Reading ease ' + state.result.grades.reading_ease + ' · ' + state.result.counts.words + ' words in ' + state.result.counts.sentences + ' sentences' }}</p>
                            <p>{{ 'Read from: ' + state.result.fields.join(', ') }}</p>
                        </details>
                    </template>
                </template>

                <ui-description
                    v-else
                    text="Grades this page as it stands here, including changes you have not saved, against the reading level the site writes for."
                />
            </div>
        `})})();
