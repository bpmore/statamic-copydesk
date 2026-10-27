let O=null;function ee(e){O||(O=document.createElement("div"),O.className="copydesk-sr-only",O.setAttribute("aria-live","polite"),O.setAttribute("role","status"),document.body.appendChild(O)),O.textContent="",setTimeout(()=>{O.textContent=e},50)}const te=/Mac|iPhone|iPad/.test(navigator.platform||navigator.userAgent),ne=te?"Option+Enter":"Alt+Enter",se=600;function K({target:e,kind:n,read:u,onResult:c}){let p=null,m=null,d=!1;async function i(){var v;p=null,m==null||m.abort(),m=new AbortController;const b=u(),C=Statamic.$app.config.globalProperties.$axios;try{const{data:x}=await C.post(cp_url("copydesk/lint"),{...e.request(),field:e.field,kind:n,value:b.value},{signal:m.signal});c(x,b)}catch(x){if((x==null?void 0:x.code)==="ERR_CANCELED"||(x==null?void 0:x.name)==="CanceledError")return;[401,403,404,419].includes((v=x==null?void 0:x.response)==null?void 0:v.status)&&k()}}function f(){d||(clearTimeout(p),p=setTimeout(i,se))}function k(){d=!0,clearTimeout(p),m==null||m.abort()}return{schedule:f,stop:k,now:i}}const{reactive:oe}=window.Vue,B=oe({fields:{}});function U(e,n){B.fields[e]=n}function G(e){delete B.fields[e]}const W={error:{word:"To fix",order:0},warning:{word:"To look at",order:1},tip:{word:"Tip",order:2},info:{word:"Tip",order:2}};function N(e){return W[e.severity]?e.severity:"warning"}function J(e){const n=e.trim();return n===""?"Remove it":"Use “"+n+"”"}let V=null;function ie({anchor:e,issue:n,focus:u,onReplace:c,onIgnore:p,onReturn:m}){var P;$();const d=N(n),i=document.createElement("div"),f="copydesk-popover-"+Math.random().toString(36).slice(2);i.className="copydesk-popover",i.dataset.severity=d,i.setAttribute("role","dialog"),i.setAttribute("aria-labelledby",f);const k=document.createElement("p");k.className="copydesk-popover__kind",k.textContent=W[d].word;const b=document.createElement("p");b.className="copydesk-popover__message",b.id=f,b.textContent=n.message;const C=document.createElement("div");C.className="copydesk-popover__actions";const v=(a,r,h=!1)=>{const l=document.createElement("button");return l.type="button",l.className="copydesk-popover__button"+(h?" copydesk-popover__button--primary":""),l.textContent=a,l.addEventListener("click",()=>{$(),r()}),C.appendChild(l),l};for(const a of n.suggestions??[])v(J(a),()=>c(a),!0);v("Ignore here",p),v("Close",m),i.append(k,b,C),document.body.appendChild(i),F(i,e()),i.addEventListener("keydown",a=>{a.key==="Escape"&&(a.preventDefault(),a.stopPropagation(),$(),m())}),i.addEventListener("focusout",a=>{x()&&!i.contains(a.relatedTarget)&&$()});const x=()=>(V==null?void 0:V.box)===i,M=a=>{x()&&!i.contains(a.target)&&$()},I=()=>{if(!x())return;const a=e();a?F(i,a):$()};setTimeout(()=>x()&&document.addEventListener("mousedown",M,!0)),window.addEventListener("scroll",I,!0),V={box:i,issue:n,teardown(){document.removeEventListener("mousedown",M,!0),window.removeEventListener("scroll",I,!0)}},u&&((P=C.querySelector("button"))==null||P.focus())}function $(){if(!V)return;const{box:e,teardown:n}=V;V=null,n(),e.remove()}function z(){return(V==null?void 0:V.issue)??null}function F(e,n){const p=e.offsetWidth,m=e.offsetHeight,d=n.bottom+6,i=d+m>window.innerHeight-8&&n.top-6-m>8?n.top-6-m:d;e.style.top=Math.round(i)+"px",e.style.left=Math.round(Math.max(8,Math.min(n.left,window.innerWidth-p-8)))+"px"}function re(e,n){if(n.path==null||n.offset==null||n.length==null)return null;let u=e,c=0;for(const d of String(n.path).split(".")){if(!/^\d+$/.test(d))return null;const i=Number(d);if(i>=u.childCount)return null;for(let f=0;f<i;f++)c+=u.child(f).nodeSize;u=u.child(i),c+=1}if(!u.isTextblock)return null;const p=Y(u,c,n.offset,1),m=Y(u,c,n.offset+n.length,-1);return p===null||m===null||m<=p?null:{from:p,to:m}}function Y(e,n,u,c){let p=0,m=n;for(let d=0;d<e.childCount;d++){const i=e.child(d),f=i.isText?i.text.length:i.type.name==="hardBreak"?1:0;if(f>0&&(u<p+f||c<0&&u===p+f))return m+(u-p);p+=f,m+=i.nodeSize}return u===p?m:null}function Q(e,n,u){const c=(e.match(/[ \t]+$/)??[""])[0].length,p=(n.match(/^[ \t]+/)??[""])[0].length;return u===""?{back:0,forward:c>0||e===""?p:0}:{back:/^[,.;:!?)\]]/.test(u)?c:0,forward:/[ \t]$/.test(u)?p:0}}const{unref:D,watch:ae}=window.Vue,le="readability_panel",q="copydesk_ignores";function H(e){var u;const n=(u=D(e==null?void 0:e.values))==null?void 0:u[q];return Array.isArray(n)?n.filter(c=>typeof c=="string"):[]}function ce(e,n){n&&e.setFieldValue(q,[...new Set([...H(e),n])])}function ue(e){e.setFieldValue(q,[])}function X(e,{handle:n,fieldPathPrefix:u,config:c},p){const m=Z(D(e==null?void 0:e.blueprint));if(!m)return null;const d=(u||n||"").split("."),i=d.length>1;return!(m.prose_fields??[]).includes(d[0])||i&&p==="text"?null:{field:d[0],key:[u,n].filter(Boolean).join(".")||n,label:(c==null?void 0:c.display)||n,collection:m.collection??null,ignores:()=>H(e),ignore:f=>ce(e,f),onIgnoresChange:f=>ae(()=>H(e).join(`
`),f),request:()=>({reference:D(e.reference)||null,site:D(e.site)||null,collection:m.collection??null})}}function Z(e){if(!e||typeof e!="object")return null;if(e.handle===le)return e.config??e;for(const n of Object.values(e)){const u=Z(n);if(u)return u}return null}const de=e=>e.rule+"\0"+e.excerpt;function fe({bard:e,tiptap:n}){const u=X(e.publishContainer,e,"bard");if(!u)return[];const{Extension:c}=n.core,{Plugin:p,PluginKey:m,TextSelection:d}=n.pm.state,{Decoration:i,DecorationSet:f}=n.pm.view,k=new m("copydeskLint"),b=a=>new p({key:k,state:{init:()=>({set:f.empty,maps:[]}),apply(r,h){let{set:l,maps:s}=h;const o=r.getMeta(k);return r.docChanged&&(l=l.map(r.mapping,r.doc),s.push(r.mapping)),o!=null&&o.sent&&(s=[]),o!=null&&o.found&&(l=f.create(r.doc,o.found.map(({from:w,to:_,issue:t})=>i.inline(w,_,{class:"copydesk-issue copydesk-issue--"+N(t),"data-copydesk-rule":t.rule},{issue:t})))),o!=null&&o.remove&&(l=l.remove(l.find(void 0,void 0,w=>o.remove(w.issue)))),{set:l,maps:s}}},props:{decorations:r=>k.getState(r).set,handleClick(r,h){const l=C(r.state,h);return l&&x(r,l,!1),!1},handleKeyDown(r,h){if(!(h.altKey&&h.key==="Enter"))return!1;const l=C(r.state,r.state.selection.from);return l?(x(r,l,!0),!0):!1}},view(r){let h=null;const l=K({target:u,kind:"bard",read(){return r.dispatch(r.state.tr.setMeta(k,{sent:!0}).setMeta("addToHistory",!1)),{value:a.getJSON().content??[],doc:r.state.doc}},onResult(o,w){if(r.isDestroyed)return;const{maps:_}=k.getState(r.state),t=u.ignores(),g=[];for(const y of o.issues??[]){const S=t.includes(y.fingerprint)?null:re(w.doc,y);if(!S)continue;const T=_.reduce((L,A)=>A.map(L,1),S.from),j=_.reduce((L,A)=>A.map(L,-1),S.to);T<j&&g.push({from:T,to:j,issue:y})}r.dispatch(r.state.tr.setMeta(k,{found:g}).setMeta("addToHistory",!1)),P(r)}});r.editable&&l.schedule();const s=u.onIgnoresChange(()=>l.schedule());return{update(o,w){!o.state.doc.eq(w.doc)&&o.editable&&l.schedule();const{selection:_}=o.state,t=_.empty?C(o.state,_.from):null,g=t?de(t.issue):null;g&&g!==h&&ee(W[N(t.issue)].word+": "+t.issue.message+" "+ne+" for choices."),h=g;const y=z();y&&!v(o.state,y)&&o.hasFocus()&&$()},destroy(){l.stop(),s(),G(u.key),v(r.state,z())&&$()}}}});function C(a,r){const h=k.getState(a).set.find(r,r).filter(s=>s.from<=r&&r<=s.to);if(!h.length)return null;const l=h.reduce((s,o)=>o.to-o.from<s.to-s.from?o:s);return{from:l.from,to:l.to,issue:l.spec.issue}}function v(a,r){if(!r)return null;const h=s=>s===r||s.rule===r.rule&&s.excerpt===r.excerpt&&s.path===r.path&&s.offset===r.offset,l=k.getState(a).set.find(void 0,void 0,s=>h(s.issue))[0];return l?{from:l.from,to:l.to,issue:r}:null}function x(a,r,h){ie({anchor:()=>{const s=a.isDestroyed?null:v(a.state,r.issue);if(!s)return null;const o=a.coordsAtPos(s.from),w=a.coordsAtPos(s.to);return{left:o.left,top:Math.min(o.top,w.top),bottom:Math.max(o.bottom,w.bottom)}},issue:r.issue,focus:h,onReplace:s=>M(a,r.issue,s),onIgnore:()=>I(a,r.issue),onReturn:()=>a.focus()})}function M(a,r,h){const l=v(a.state,r);if(!l){a.focus();return}const{doc:s}=a.state,o=s.resolve(l.from),w=s.resolve(l.to),_=s.textBetween(o.start(),l.from,`
`,`
`),t=s.textBetween(l.to,w.end(),`
`,`
`),{back:g,forward:y}=Q(_,t,h),S=l.from-g,T=a.state.tr.insertText(h,S,l.to+y);T.setSelection(d.create(T.doc,S+h.length)),a.dispatch(T.scrollIntoView()),a.focus()}function I(a,r){u.ignore(r.fingerprint),a.dispatch(a.state.tr.setMeta(k,{remove:h=>h.fingerprint===r.fingerprint}).setMeta("addToHistory",!1)),a.focus(),P(a)}function P(a){const r=k.getState(a.state).set.find().sort((h,l)=>h.from-l.from).map(h=>({issue:h.spec.issue,reveal(){const l=v(a.state,h.spec.issue);return l?(a.dispatch(a.state.tr.setSelection(d.create(a.state.doc,l.from,l.to)).scrollIntoView()),a.focus(),requestAnimationFrame(()=>x(a,l,!1)),!0):!1}}));U(u.key,{label:u.label,issues:r})}return c.create({name:"copydeskLint",addProseMirrorPlugins(){return[b(this.editor)]}})}const{h:E,ref:pe,shallowRef:ge,defineComponent:me,onMounted:he,onUpdated:ye,onBeforeUnmount:ve}=window.Vue;function ke(e,n){return me({name:"CopydeskIssueList",inheritAttrs:!1,setup(u,{attrs:c,expose:p}){const m=pe(null);p(new Proxy({},{get:(s,o)=>{var w;return(w=m.value)==null?void 0:w[o]},has:(s,o)=>m.value!=null&&o in m.value}));let d=null;try{d=window.__STATAMIC__.ui.injectPublishContext()}catch{d=null}const i=X(d,{handle:c.handle,fieldPathPrefix:c.fieldPathPrefix,config:c.config},n);if(!i)return()=>E(e,{...c,ref:m});const f=ge({issues:[],value:null});let k=c.value;const b=K({target:i,kind:n,read:()=>({value:typeof c.value=="string"?c.value:""}),onResult(s,o){f.value={issues:s.issues??[],value:o.value},h()}});he(()=>b.schedule()),ye(()=>{c.value!==k&&(k=c.value,b.schedule())});const C=i.onIgnoresChange(()=>h());ve(()=>{b.stop(),C(),G(i.key)});const v=()=>{const s=i.ignores();return f.value.issues.filter(o=>!s.includes(o.fingerprint))},x=s=>s.offset!=null&&n!=="markdown",M=()=>c.id?document.getElementById(c.id):null,I=s=>s&&HTMLElement.prototype.focus.call(s);function P(s,o){var L;const w=typeof c.value=="string"?c.value:"";if(w!==f.value.value){b.now();return}const _=w.replace(/\r\n?/g,`
`),t=s.offset,g=s.offset+s.length,{back:y,forward:S}=Q(_.slice(0,t),_.slice(g),o),T=_.slice(0,t-y)+o+_.slice(g+S),j=t-y+o.length;(L=c["onUpdate:value"])==null||L.call(c,T),f.value={issues:[],value:null},requestAnimationFrame(()=>{var R;const A=M();I(A),(R=A==null?void 0:A.setSelectionRange)==null||R.call(A,j,j)})}function a(s){i.ignore(s.fingerprint),h()}function r(s){var w,_;const o=M();return o&&x(s)?(I(o),(w=o.setSelectionRange)==null||w.call(o,s.offset,s.offset+s.length)):((_=document.querySelector('[data-copydesk-list="'+CSS.escape(i.key)+'"]'))==null||_.scrollIntoView({block:"center"}),I(o)),!0}function h(){U(i.key,{label:i.label,issues:v().map(s=>({issue:s,reveal:()=>r(s)}))})}function l(){const s=v();return s.length?E("div",{class:"copydesk-field-issues","data-copydesk-list":i.key},[E("p",{class:"copydesk-sr-only"},s.length===1?"Copydesk found 1 thing in this field.":"Copydesk found "+s.length+" things in this field."),E("ul",{class:"copydesk-field-issues__list"},s.map((o,w)=>{const _=N(o);return E("li",{key:w,class:"copydesk-field-issue","data-severity":_},[E("span",{class:"copydesk-field-issue__kind"},W[_].word)," ",E("span",{class:"copydesk-field-issue__message"},o.message),o.excerpt?[" ",E("mark",{class:"copydesk-field-issue__excerpt"},o.excerpt)]:null,E("span",{class:"copydesk-field-issue__actions"},[...x(o)?(o.suggestions??[]).map(t=>E("button",{type:"button",class:"copydesk-popover__button copydesk-popover__button--primary",onClick:()=>P(o,t)},J(t))):(o.suggestions??[]).length?[E("span",{class:"copydesk-field-issue__try"},"Try: "+o.suggestions.map(t=>t.trim()||"leaving it out").join(", "))]:[],E("button",{type:"button",class:"copydesk-popover__button",onClick:()=>a(o)},"Ignore here")])])}))]):null}return()=>[E(e,{...c,ref:m}),l()]}})}Statamic.$bard.addExtension(fe);Statamic.configuring(()=>{const e=Statamic.$app;for(const[n,u]of[["markdown-fieldtype","markdown"],["textarea-fieldtype","textarea"],["text-fieldtype","text"]]){const c=e.component(n);c&&e.component(n,ke(c,u))}});(function(){const{injectPublishContext:e}=window.__STATAMIC__.ui,n=[{key:"error",color:"red",one:"1 to fix",many:d=>d+" to fix"},{key:"warning",color:"amber",one:"1 to look at",many:d=>d+" to look at"},{key:"tip",color:"gray",one:"1 tip",many:d=>d+" tips"}],u={on_target:{color:"emerald",words:"on target"},above:{color:"red",words:"harder than your target"},below:{color:"emerald",words:"easier than your target, which is fine"}};function c(d){return String(d).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;")}const p=70;function m(d){if(!d)return"";const i=Array.from(d.text);let f=i.slice(0,d.offset).join("");const k=i.slice(d.offset,d.offset+d.length).join("");let b=i.slice(d.offset+d.length).join("");return i.length>2*p&&d.length<i.length&&(f.length>p&&(f="…"+f.slice(-p).replace(/^\S*\s/,"")),b.length>p&&(b=b.slice(0,p).replace(/\s\S*$/,"")+"…")),c(f)+"<mark>"+c(k)+"</mark>"+c(b)}Statamic.$components.register("readability_panel-fieldtype",{props:["config","meta"],setup(d){const i=e(),f=Vue.reactive({checking:!1,result:null,failed:null,notYet:!1});async function k(){var g,y,S,T,j,L,A;f.checking=!0,f.failed=null,f.notYet=!1;const t=Statamic.$app.config.globalProperties.$axios;try{const{data:R}=await t.post(cp_url("copydesk/check"),{reference:(g=i.reference)==null?void 0:g.value,values:i.values.value,site:(y=i.site)==null?void 0:y.value,collection:(S=d.config)==null?void 0:S.collection,blueprint:(T=d.config)==null?void 0:T.blueprint});f.result=R}catch(R){f.notYet=((j=R.response)==null?void 0:j.status)===422,f.failed=((A=(L=R.response)==null?void 0:L.data)==null?void 0:A.message)||"The check could not be run.",f.result=null}finally{f.checking=!1}}const b=Vue.computed(()=>{var g;const t=(((g=i.errors)==null?void 0:g.value)??{}).copydesk;return Array.isArray(t)?t:[]});Vue.watch(b,t=>{t.length>0&&(f.result=null,f.failed=null)});const C=Vue.computed(()=>b.value.length>0&&!f.result&&!f.failed&&!f.checking),v=Vue.reactive({open:!1,reason:"",sending:!1,done:null,failed:null});async function x(){var t,g,y,S;v.sending=!0,v.failed=null;try{const{data:T}=await Statamic.$app.config.globalProperties.$axios.post(cp_url("copydesk/override"),{reference:(t=i.reference)==null?void 0:t.value,collection:(g=d.config)==null?void 0:g.collection,reason:v.reason});v.done=T.message,v.open=!1}catch(T){v.failed=((S=(y=T.response)==null?void 0:y.data)==null?void 0:S.message)||"The pass could not be given."}finally{v.sending=!1}}Vue.watch(b,()=>{v.done=null,v.failed=null});const M=Vue.computed(()=>{const t=f.result;if(!t||!t.band)return null;const g=u[t.comparison]??u.on_target;return{color:g.color,text:"Your target is "+t.target.label+". This page reads "+g.words+"."}}),I=Vue.computed(()=>{var g;const t=((g=f.result)==null?void 0:g.findings)??[];return n.map(y=>{const S=t.filter(T=>T.severity===y.key);return{key:y.key,color:y.color,items:S,badge:S.length===1?y.one:y.many(S.length)}}).filter(y=>y.items.length>0)}),P=Vue.computed(()=>{var t;return(((t=f.result)==null?void 0:t.sections)??[]).map(g=>{var y;return{...g,name:g.lead?"Before the first heading":g.title,color:g.band?((y=u[g.comparison])==null?void 0:y.color)??"gray":"gray",label:g.band?g.band.label:"Nothing to grade"}})});function a(t){return t.slice(0,5)}const r=Vue.computed(()=>Object.values(B.fields).flatMap(t=>t.issues.map(g=>({...g,label:t.label}))).sort((t,g)=>W[N(t.issue)].order-W[N(g.issue)].order)),h=Vue.computed(()=>n.map(t=>{const g=r.value.filter(y=>N(y.issue)===t.key||t.key==="tip"&&y.issue.severity==="info").length;return{key:t.key,color:t.color,n:g,badge:g===1?t.one:t.many(g)}}).filter(t=>t.n>0)),l=20,s=Vue.computed(()=>H(i).length);function o(t){return t===1?"1 thing is ignored on this page.":t+" things are ignored on this page."}function w(){ue(i)}function _(t){const g=Array.from(t.issue.excerpt||""),y=g.length>32?g.slice(0,30).join("").replace(/\s+\S*$/,"")+"…":g.join("");return"Show "+(y?"“"+y+"”":"it")+" in "+t.label}return{override:v,askToOverride:x,state:f,check:k,verdict:M,groups:I,sections:P,highlighted:m,shown:a,refusal:b,showRefusal:C,liveItems:r,liveCounts:h,LIVE_SHOWN:l,jumpLabel:_,ignoredCount:s,ignoredWords:o,stopIgnoring:w,severityWord:t=>W[N(t)].word}},template:`
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

                <div v-if="showRefusal && meta && meta.canOverride" class="space-y-2">
                    <ui-description v-if="override.done" :text="override.done" />
                    <div v-else-if="!override.open">
                        <ui-button size="sm" text="Publish anyway" @click="override.open = true" />
                    </div>
                    <form v-else class="space-y-2" @submit.prevent="askToOverride">
                        <label class="block text-sm" for="copydesk-override-reason">Why should this page publish as it is?</label>
                        <textarea
                            id="copydesk-override-reason"
                            v-model="override.reason"
                            class="copydesk-reason"
                            rows="3"
                            required
                            aria-describedby="copydesk-override-help"
                        ></textarea>
                        <p id="copydesk-override-help" class="text-xs">A few words. They are kept with the page in the site report.</p>
                        <ui-alert v-if="override.failed" variant="error" :text="override.failed" />
                        <div class="flex gap-2">
                            <div><ui-button size="sm" type="submit" variant="primary" :disabled="override.sending" :text="override.sending ? 'Sending' : 'Allow one publish'" /></div>
                            <div><ui-button size="sm" text="Cancel" @click="override.open = false" /></div>
                        </div>
                    </form>
                </div>

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
        `})})();(function(){Statamic.$components.register("copydesk-scan-progress",{props:["url"],setup(e){const n=Vue.reactive({done:0,total:null});let u=null;async function c(){try{const{data:p}=await Statamic.$app.config.globalProperties.$axios.get(e.url);if(p.total===null){window.location.reload();return}n.done=p.done,n.total=p.total}catch{}u=setTimeout(c,2e3)}return Vue.onMounted(c),Vue.onBeforeUnmount(()=>clearTimeout(u)),{state:n}},template:`
            <div class="space-y-2" role="status" aria-live="polite">
                <p class="text-sm">{{ state.total === null ? 'Starting the scan.' : 'Scanning: ' + state.done + ' of ' + state.total + ' pages done.' }}</p>
                <p class="text-xs">The report shows here when it is done. You can leave this page; the scan carries on.</p>
            </div>
        `})})();(function(){const{injectPublishContext:e}=window.__STATAMIC__.ui,n={error:"To fix",warning:"To look at",tip:"Tip"};Statamic.$components.register("copydesk_rule_tester-fieldtype",{props:["meta"],setup(u){const c=e(),p=Vue.reactive({text:"",trying:!1,findings:null,failed:null});async function m(){var d,i,f,k,b,C;p.trying=!0,p.failed=null;try{const{data:v}=await Statamic.$app.config.globalProperties.$axios.post(u.meta.url,{values:c.values.value,text:p.text});p.findings=v.findings}catch(v){p.findings=null,p.failed=((k=(f=(i=(d=v.response)==null?void 0:d.data)==null?void 0:i.errors)==null?void 0:f.rules)==null?void 0:k[0])||((C=(b=v.response)==null?void 0:b.data)==null?void 0:C.message)||"The rules could not be tried."}finally{p.trying=!1}}return{state:p,run:m,WORDS:n}},template:`
            <div class="space-y-3">
                <label class="block text-sm" for="copydesk-try-text">Text to check</label>
                <textarea id="copydesk-try-text" v-model="state.text" class="copydesk-reason" rows="5"></textarea>
                <div><ui-button size="sm" :disabled="state.trying || !meta.url" :text="state.trying ? 'Trying' : 'Try these rules'" @click="run" /></div>
                <div role="status" aria-live="polite" class="space-y-2">
                    <ui-alert v-if="state.failed" variant="error" :text="state.failed" />
                    <p v-else-if="state.findings && !state.findings.length" class="text-sm">Nothing flagged.</p>
                    <ul v-else-if="state.findings" class="space-y-2 text-sm">
                        <li v-for="(f, i) in state.findings" :key="i">
                            <strong>{{ WORDS[f.severity] || f.severity }}</strong>
                            <span>{{ ' ' + f.rule + ': ' + f.message + ' ' }}</span>
                            <mark>{{ f.excerpt }}</mark>
                        </li>
                    </ul>
                </div>
                <p class="text-xs">Saving rewrites the pack's file, so comments in it are not kept. A pack's changes for single collections are kept as they are.</p>
            </div>
        `})})();
