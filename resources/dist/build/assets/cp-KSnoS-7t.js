let N=null;function ee(e){N||(N=document.createElement("div"),N.className="copydesk-sr-only",N.setAttribute("aria-live","polite"),N.setAttribute("role","status"),document.body.appendChild(N)),N.textContent="",setTimeout(()=>{N.textContent=e},50)}const te=/Mac|iPhone|iPad/.test(navigator.platform||navigator.userAgent),ne=te?"Option+Enter":"Alt+Enter",se=600;function K({target:e,kind:i,read:f,onResult:d}){let y=null,g=null,c=!1;async function r(){var v;y=null,g==null||g.abort(),g=new AbortController;const w=f(),E=Statamic.$app.config.globalProperties.$axios;try{const{data:k}=await E.post(cp_url("copydesk/lint"),{...e.request(),field:e.field,kind:i,value:w.value},{signal:g.signal});d(k,w)}catch(k){if((k==null?void 0:k.code)==="ERR_CANCELED"||(k==null?void 0:k.name)==="CanceledError")return;[401,403,404,419].includes((v=k==null?void 0:k.response)==null?void 0:v.status)&&x()}}function u(){c||(clearTimeout(y),y=setTimeout(r,se))}function x(){c=!0,clearTimeout(y),g==null||g.abort()}return{schedule:u,stop:x,now:r}}const{reactive:oe}=window.Vue,q=oe({fields:{}});function U(e,i){q.fields[e]=i}function G(e){delete q.fields[e]}const W={error:{word:"To fix",order:0},warning:{word:"To look at",order:1},tip:{word:"Tip",order:2},info:{word:"Tip",order:2}};function R(e){return W[e.severity]?e.severity:"warning"}function J(e){const i=e.trim();return i===""?"Remove it":"Use “"+i+"”"}let V=null;function ie({anchor:e,issue:i,focus:f,onReplace:d,onIgnore:y,onReturn:g}){var M;O();const c=R(i),r=document.createElement("div"),u="copydesk-popover-"+Math.random().toString(36).slice(2);r.className="copydesk-popover",r.dataset.severity=c,r.setAttribute("role","dialog"),r.setAttribute("aria-labelledby",u);const x=document.createElement("p");x.className="copydesk-popover__kind",x.textContent=W[c].word;const w=document.createElement("p");w.className="copydesk-popover__message",w.id=u,w.textContent=i.message;const E=document.createElement("div");E.className="copydesk-popover__actions";const v=(l,o,m=!1)=>{const a=document.createElement("button");return a.type="button",a.className="copydesk-popover__button"+(m?" copydesk-popover__button--primary":""),a.textContent=l,a.addEventListener("click",()=>{O(),o()}),E.appendChild(a),a};for(const l of i.suggestions??[])v(J(l),()=>d(l),!0);v("Ignore here",y),v("Close",g),r.append(x,w,E),document.body.appendChild(r),z(r,e()),r.addEventListener("keydown",l=>{l.key==="Escape"&&(l.preventDefault(),l.stopPropagation(),O(),g())}),r.addEventListener("focusout",l=>{k()&&!r.contains(l.relatedTarget)&&O()});const k=()=>(V==null?void 0:V.box)===r,L=l=>{k()&&!r.contains(l.target)&&O()},T=()=>{if(!k())return;const l=e();l?z(r,l):O()};setTimeout(()=>k()&&document.addEventListener("mousedown",L,!0)),window.addEventListener("scroll",T,!0),V={box:r,issue:i,teardown(){document.removeEventListener("mousedown",L,!0),window.removeEventListener("scroll",T,!0)}},f&&((M=E.querySelector("button"))==null||M.focus())}function O(){if(!V)return;const{box:e,teardown:i}=V;V=null,i(),e.remove()}function F(){return(V==null?void 0:V.issue)??null}function z(e,i){const y=e.offsetWidth,g=e.offsetHeight,c=i.bottom+6,r=c+g>window.innerHeight-8&&i.top-6-g>8?i.top-6-g:c;e.style.top=Math.round(r)+"px",e.style.left=Math.round(Math.max(8,Math.min(i.left,window.innerWidth-y-8)))+"px"}function re(e,i){if(i.path==null||i.offset==null||i.length==null)return null;let f=e,d=0;for(const c of String(i.path).split(".")){if(!/^\d+$/.test(c))return null;const r=Number(c);if(r>=f.childCount)return null;for(let u=0;u<r;u++)d+=f.child(u).nodeSize;f=f.child(r),d+=1}if(!f.isTextblock)return null;const y=Y(f,d,i.offset,1),g=Y(f,d,i.offset+i.length,-1);return y===null||g===null||g<=y?null:{from:y,to:g}}function Y(e,i,f,d){let y=0,g=i;for(let c=0;c<e.childCount;c++){const r=e.child(c),u=r.isText?r.text.length:r.type.name==="hardBreak"?1:0;if(u>0&&(f<y+u||d<0&&f===y+u))return g+(f-y);y+=u,g+=r.nodeSize}return f===y?g:null}function Q(e,i,f){const d=(e.match(/[ \t]+$/)??[""])[0].length,y=(i.match(/^[ \t]+/)??[""])[0].length;return f===""?{back:0,forward:d>0||e===""?y:0}:{back:/^[,.;:!?)\]]/.test(f)?d:0,forward:/[ \t]$/.test(f)?y:0}}const{unref:H,watch:le}=window.Vue,ae="readability_panel",B="copydesk_ignores";function D(e){var f;const i=(f=H(e==null?void 0:e.values))==null?void 0:f[B];return Array.isArray(i)?i.filter(d=>typeof d=="string"):[]}function ce(e,i){i&&e.setFieldValue(B,[...new Set([...D(e),i])])}function ue(e){e.setFieldValue(B,[])}function X(e,{handle:i,fieldPathPrefix:f,config:d},y){const g=Z(H(e==null?void 0:e.blueprint));if(!g)return null;const c=(f||i||"").split("."),r=c.length>1;return!(g.prose_fields??[]).includes(c[0])||r&&y==="text"?null:{field:c[0],key:[f,i].filter(Boolean).join(".")||i,label:(d==null?void 0:d.display)||i,collection:g.collection??null,ignores:()=>D(e),ignore:u=>ce(e,u),onIgnoresChange:u=>le(()=>D(e).join(`
`),u),request:()=>({reference:H(e.reference)||null,site:H(e.site)||null,collection:g.collection??null})}}function Z(e){if(!e||typeof e!="object")return null;if(e.handle===ae)return e.config??e;for(const i of Object.values(e)){const f=Z(i);if(f)return f}return null}const de=e=>e.rule+"\0"+e.excerpt;function fe({bard:e,tiptap:i}){const f=X(e.publishContainer,e,"bard");if(!f)return[];const{Extension:d}=i.core,{Plugin:y,PluginKey:g,TextSelection:c}=i.pm.state,{Decoration:r,DecorationSet:u}=i.pm.view,x=new g("copydeskLint"),w=l=>new y({key:x,state:{init:()=>({set:u.empty,maps:[]}),apply(o,m){let{set:a,maps:n}=m;const s=o.getMeta(x);return o.docChanged&&(a=a.map(o.mapping,o.doc),n.push(o.mapping)),s!=null&&s.sent&&(n=[]),s!=null&&s.found&&(a=u.create(o.doc,s.found.map(({from:b,to:_,issue:t})=>r.inline(b,_,{class:"copydesk-issue copydesk-issue--"+R(t),"data-copydesk-rule":t.rule},{issue:t})))),s!=null&&s.remove&&(a=a.remove(a.find(void 0,void 0,b=>s.remove(b.issue)))),{set:a,maps:n}}},props:{decorations:o=>x.getState(o).set,handleClick(o,m){const a=E(o.state,m);return a&&k(o,a,!1),!1},handleKeyDown(o,m){if(!(m.altKey&&m.key==="Enter"))return!1;const a=E(o.state,o.state.selection.from);return a?(k(o,a,!0),!0):!1}},view(o){let m=null;const a=K({target:f,kind:"bard",read(){return o.dispatch(o.state.tr.setMeta(x,{sent:!0}).setMeta("addToHistory",!1)),{value:l.getJSON().content??[],doc:o.state.doc}},onResult(s,b){if(o.isDestroyed)return;const{maps:_}=x.getState(o.state),t=f.ignores(),p=[];for(const h of s.issues??[]){const S=t.includes(h.fingerprint)?null:re(b.doc,h);if(!S)continue;const C=_.reduce((P,A)=>A.map(P,1),S.from),j=_.reduce((P,A)=>A.map(P,-1),S.to);C<j&&p.push({from:C,to:j,issue:h})}o.dispatch(o.state.tr.setMeta(x,{found:p}).setMeta("addToHistory",!1)),M(o)}});o.editable&&a.schedule();const n=f.onIgnoresChange(()=>a.schedule());return{update(s,b){!s.state.doc.eq(b.doc)&&s.editable&&a.schedule();const{selection:_}=s.state,t=_.empty?E(s.state,_.from):null,p=t?de(t.issue):null;p&&p!==m&&ee(W[R(t.issue)].word+": "+t.issue.message+" "+ne+" for choices."),m=p;const h=F();h&&!v(s.state,h)&&s.hasFocus()&&O()},destroy(){a.stop(),n(),G(f.key),v(o.state,F())&&O()}}}});function E(l,o){const m=x.getState(l).set.find(o,o).filter(n=>n.from<=o&&o<=n.to);if(!m.length)return null;const a=m.reduce((n,s)=>s.to-s.from<n.to-n.from?s:n);return{from:a.from,to:a.to,issue:a.spec.issue}}function v(l,o){if(!o)return null;const m=n=>n===o||n.rule===o.rule&&n.excerpt===o.excerpt&&n.path===o.path&&n.offset===o.offset,a=x.getState(l).set.find(void 0,void 0,n=>m(n.issue))[0];return a?{from:a.from,to:a.to,issue:o}:null}function k(l,o,m){ie({anchor:()=>{const n=l.isDestroyed?null:v(l.state,o.issue);if(!n)return null;const s=l.coordsAtPos(n.from),b=l.coordsAtPos(n.to);return{left:s.left,top:Math.min(s.top,b.top),bottom:Math.max(s.bottom,b.bottom)}},issue:o.issue,focus:m,onReplace:n=>L(l,o.issue,n),onIgnore:()=>T(l,o.issue),onReturn:()=>l.focus()})}function L(l,o,m){const a=v(l.state,o);if(!a){l.focus();return}const{doc:n}=l.state,s=n.resolve(a.from),b=n.resolve(a.to),_=n.textBetween(s.start(),a.from,`
`,`
`),t=n.textBetween(a.to,b.end(),`
`,`
`),{back:p,forward:h}=Q(_,t,m),S=a.from-p,C=l.state.tr.insertText(m,S,a.to+h);C.setSelection(c.create(C.doc,S+m.length)),l.dispatch(C.scrollIntoView()),l.focus()}function T(l,o){f.ignore(o.fingerprint),l.dispatch(l.state.tr.setMeta(x,{remove:m=>m.fingerprint===o.fingerprint}).setMeta("addToHistory",!1)),l.focus(),M(l)}function M(l){const o=x.getState(l.state).set.find().sort((m,a)=>m.from-a.from).map(m=>({issue:m.spec.issue,reveal(){const a=v(l.state,m.spec.issue);return a?(l.dispatch(l.state.tr.setSelection(c.create(l.state.doc,a.from,a.to)).scrollIntoView()),l.focus(),requestAnimationFrame(()=>k(l,a,!1)),!0):!1}}));U(f.key,{label:f.label,issues:o})}return d.create({name:"copydeskLint",addProseMirrorPlugins(){return[w(this.editor)]}})}const{h:I,ref:pe,shallowRef:ge,defineComponent:me,onMounted:he,onUpdated:ye,onBeforeUnmount:ve}=window.Vue;function ke(e,i){return me({name:"CopydeskIssueList",inheritAttrs:!1,setup(f,{attrs:d,expose:y}){const g=pe(null);y(new Proxy({},{get:(n,s)=>{var b;return(b=g.value)==null?void 0:b[s]},has:(n,s)=>g.value!=null&&s in g.value}));let c=null;try{c=window.__STATAMIC__.ui.injectPublishContext()}catch{c=null}const r=X(c,{handle:d.handle,fieldPathPrefix:d.fieldPathPrefix,config:d.config},i);if(!r)return()=>I(e,{...d,ref:g});const u=ge({issues:[],value:null});let x=d.value;const w=K({target:r,kind:i,read:()=>({value:typeof d.value=="string"?d.value:""}),onResult(n,s){u.value={issues:n.issues??[],value:s.value},m()}});he(()=>w.schedule()),ye(()=>{d.value!==x&&(x=d.value,w.schedule())});const E=r.onIgnoresChange(()=>m());ve(()=>{w.stop(),E(),G(r.key)});const v=()=>{const n=r.ignores();return u.value.issues.filter(s=>!n.includes(s.fingerprint))},k=n=>n.offset!=null&&i!=="markdown",L=()=>d.id?document.getElementById(d.id):null,T=n=>n&&HTMLElement.prototype.focus.call(n);function M(n,s){var P;const b=typeof d.value=="string"?d.value:"";if(b!==u.value.value){w.now();return}const _=b.replace(/\r\n?/g,`
`),t=n.offset,p=n.offset+n.length,{back:h,forward:S}=Q(_.slice(0,t),_.slice(p),s),C=_.slice(0,t-h)+s+_.slice(p+S),j=t-h+s.length;(P=d["onUpdate:value"])==null||P.call(d,C),u.value={issues:[],value:null},requestAnimationFrame(()=>{var $;const A=L();T(A),($=A==null?void 0:A.setSelectionRange)==null||$.call(A,j,j)})}function l(n){r.ignore(n.fingerprint),m()}function o(n){var b,_;const s=L();return s&&k(n)?(T(s),(b=s.setSelectionRange)==null||b.call(s,n.offset,n.offset+n.length)):((_=document.querySelector('[data-copydesk-list="'+CSS.escape(r.key)+'"]'))==null||_.scrollIntoView({block:"center"}),T(s)),!0}function m(){U(r.key,{label:r.label,issues:v().map(n=>({issue:n,reveal:()=>o(n)}))})}function a(){const n=v();return n.length?I("div",{class:"copydesk-field-issues","data-copydesk-list":r.key},[I("p",{class:"copydesk-sr-only"},n.length===1?"Copydesk found 1 thing in this field.":"Copydesk found "+n.length+" things in this field."),I("ul",{class:"copydesk-field-issues__list"},n.map((s,b)=>{const _=R(s);return I("li",{key:b,class:"copydesk-field-issue","data-severity":_},[I("span",{class:"copydesk-field-issue__kind"},W[_].word)," ",I("span",{class:"copydesk-field-issue__message"},s.message),s.excerpt?[" ",I("mark",{class:"copydesk-field-issue__excerpt"},s.excerpt)]:null,I("span",{class:"copydesk-field-issue__actions"},[...k(s)?(s.suggestions??[]).map(t=>I("button",{type:"button",class:"copydesk-popover__button copydesk-popover__button--primary",onClick:()=>M(s,t)},J(t))):(s.suggestions??[]).length?[I("span",{class:"copydesk-field-issue__try"},"Try: "+s.suggestions.map(t=>t.trim()||"leaving it out").join(", "))]:[],I("button",{type:"button",class:"copydesk-popover__button",onClick:()=>l(s)},"Ignore here")])])}))]):null}return()=>[I(e,{...d,ref:g}),a()]}})}Statamic.$bard.addExtension(fe);Statamic.configuring(()=>{const e=Statamic.$app;for(const[i,f]of[["markdown-fieldtype","markdown"],["textarea-fieldtype","textarea"],["text-fieldtype","text"]]){const d=e.component(i);d&&e.component(i,ke(d,f))}});(function(){const{injectPublishContext:e}=window.__STATAMIC__.ui,i=[{key:"error",color:"red",one:"1 to fix",many:c=>c+" to fix"},{key:"warning",color:"amber",one:"1 to look at",many:c=>c+" to look at"},{key:"tip",color:"gray",one:"1 tip",many:c=>c+" tips"}],f={on_target:{color:"emerald",words:"on target"},above:{color:"red",words:"harder than your target"},below:{color:"emerald",words:"easier than your target, which is fine"}};function d(c){return String(c).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;")}const y=70;function g(c){if(!c)return"";const r=Array.from(c.text);let u=r.slice(0,c.offset).join("");const x=r.slice(c.offset,c.offset+c.length).join("");let w=r.slice(c.offset+c.length).join("");return r.length>2*y&&c.length<r.length&&(u.length>y&&(u="…"+u.slice(-y).replace(/^\S*\s/,"")),w.length>y&&(w=w.slice(0,y).replace(/\s\S*$/,"")+"…")),d(u)+"<mark>"+d(x)+"</mark>"+d(w)}Statamic.$components.register("readability_panel-fieldtype",{props:["config","meta"],setup(c){const r=e(),u=Vue.reactive({checking:!1,result:null,failed:null,notYet:!1});async function x(){var p,h,S,C,j,P,A;u.checking=!0,u.failed=null,u.notYet=!1;const t=Statamic.$app.config.globalProperties.$axios;try{const{data:$}=await t.post(cp_url("copydesk/check"),{reference:(p=r.reference)==null?void 0:p.value,values:r.values.value,site:(h=r.site)==null?void 0:h.value,collection:(S=c.config)==null?void 0:S.collection,blueprint:(C=c.config)==null?void 0:C.blueprint});u.result=$}catch($){u.notYet=((j=$.response)==null?void 0:j.status)===422,u.failed=((A=(P=$.response)==null?void 0:P.data)==null?void 0:A.message)||"The check could not be run.",u.result=null}finally{u.checking=!1}}const w=Vue.computed(()=>{var p;const t=(((p=r.errors)==null?void 0:p.value)??{}).copydesk;return Array.isArray(t)?t:[]});Vue.watch(w,t=>{t.length>0&&(u.result=null,u.failed=null)});const E=Vue.computed(()=>w.value.length>0&&!u.result&&!u.failed&&!u.checking),v=Vue.reactive({open:!1,reason:"",sending:!1,done:null,failed:null});async function k(){var t,p,h,S;v.sending=!0,v.failed=null;try{const{data:C}=await Statamic.$app.config.globalProperties.$axios.post(cp_url("copydesk/override"),{reference:(t=r.reference)==null?void 0:t.value,collection:(p=c.config)==null?void 0:p.collection,reason:v.reason});v.done=C.message,v.open=!1}catch(C){v.failed=((S=(h=C.response)==null?void 0:h.data)==null?void 0:S.message)||"The pass could not be given."}finally{v.sending=!1}}Vue.watch(w,()=>{v.done=null,v.failed=null});const L=Vue.computed(()=>{const t=u.result;if(!t||!t.band)return null;const p=f[t.comparison]??f.on_target;return{color:p.color,text:"Your target is "+t.target.label+". This page reads "+p.words+"."}}),T=Vue.computed(()=>{var p;const t=((p=u.result)==null?void 0:p.findings)??[];return i.map(h=>{const S=t.filter(C=>C.severity===h.key);return{key:h.key,color:h.color,items:S,badge:S.length===1?h.one:h.many(S.length)}}).filter(h=>h.items.length>0)}),M=Vue.computed(()=>{var t;return(((t=u.result)==null?void 0:t.sections)??[]).map(p=>{var h;return{...p,name:p.lead?"Before the first heading":p.title,color:p.band?((h=f[p.comparison])==null?void 0:h.color)??"gray":"gray",label:p.band?p.band.label:"Nothing to grade"}})});function l(t){return t.slice(0,5)}const o=Vue.computed(()=>Object.values(q.fields).flatMap(t=>t.issues.map(p=>({...p,label:t.label}))).sort((t,p)=>W[R(t.issue)].order-W[R(p.issue)].order)),m=Vue.computed(()=>i.map(t=>{const p=o.value.filter(h=>R(h.issue)===t.key||t.key==="tip"&&h.issue.severity==="info").length;return{key:t.key,color:t.color,n:p,badge:p===1?t.one:t.many(p)}}).filter(t=>t.n>0)),a=20,n=Vue.computed(()=>D(r).length);function s(t){return t===1?"1 thing is ignored on this page.":t+" things are ignored on this page."}function b(){ue(r)}function _(t){const p=Array.from(t.issue.excerpt||""),h=p.length>32?p.slice(0,30).join("").replace(/\s+\S*$/,"")+"…":p.join("");return"Show "+(h?"“"+h+"”":"it")+" in "+t.label}return{override:v,askToOverride:k,state:u,check:x,verdict:L,groups:T,sections:M,highlighted:g,shown:l,refusal:w,showRefusal:E,liveItems:o,liveCounts:m,LIVE_SHOWN:a,jumpLabel:_,ignoredCount:n,ignoredWords:s,stopIgnoring:b,severityWord:t=>W[R(t)].word}},template:`
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
        `})})();
