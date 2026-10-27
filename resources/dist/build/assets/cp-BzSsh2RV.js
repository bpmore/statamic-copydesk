let N=null;function ee(e){N||(N=document.createElement("div"),N.className="copydesk-sr-only",N.setAttribute("aria-live","polite"),N.setAttribute("role","status"),document.body.appendChild(N)),N.textContent="",setTimeout(()=>{N.textContent=e},50)}const te=/Mac|iPhone|iPad/.test(navigator.platform||navigator.userAgent),ne=te?"Option+Enter":"Alt+Enter",se=600;function K({target:e,kind:o,read:u,onResult:c}){let g=null,m=null,d=!1;async function r(){var v;g=null,m==null||m.abort(),m=new AbortController;const w=u(),E=Statamic.$app.config.globalProperties.$axios;try{const{data:k}=await E.post(cp_url("copydesk/lint"),{...e.request(),field:e.field,kind:o,value:w.value},{signal:m.signal});c(k,w)}catch(k){if((k==null?void 0:k.code)==="ERR_CANCELED"||(k==null?void 0:k.name)==="CanceledError")return;[401,403,404,419].includes((v=k==null?void 0:k.response)==null?void 0:v.status)&&x()}}function f(){d||(clearTimeout(g),g=setTimeout(r,se))}function x(){d=!0,clearTimeout(g),m==null||m.abort()}return{schedule:f,stop:x,now:r}}const{reactive:oe}=window.Vue,D=oe({fields:{}});function U(e,o){D.fields[e]=o}function G(e){delete D.fields[e]}const W={error:{word:"To fix",order:0},warning:{word:"To look at",order:1},tip:{word:"Tip",order:2},info:{word:"Tip",order:2}};function R(e){return W[e.severity]?e.severity:"warning"}function J(e){const o=e.trim();return o===""?"Remove it":"Use “"+o+"”"}let V=null;function ie({anchor:e,issue:o,focus:u,onReplace:c,onIgnore:g,onReturn:m}){var L;O();const d=R(o),r=document.createElement("div"),f="copydesk-popover-"+Math.random().toString(36).slice(2);r.className="copydesk-popover",r.dataset.severity=d,r.setAttribute("role","dialog"),r.setAttribute("aria-labelledby",f);const x=document.createElement("p");x.className="copydesk-popover__kind",x.textContent=W[d].word;const w=document.createElement("p");w.className="copydesk-popover__message",w.id=f,w.textContent=o.message;const E=document.createElement("div");E.className="copydesk-popover__actions";const v=(a,i,h=!1)=>{const l=document.createElement("button");return l.type="button",l.className="copydesk-popover__button"+(h?" copydesk-popover__button--primary":""),l.textContent=a,l.addEventListener("click",()=>{O(),i()}),E.appendChild(l),l};for(const a of o.suggestions??[])v(J(a),()=>c(a),!0);v("Ignore here",g),v("Close",m),r.append(x,w,E),document.body.appendChild(r),z(r,e()),r.addEventListener("keydown",a=>{a.key==="Escape"&&(a.preventDefault(),a.stopPropagation(),O(),m())}),r.addEventListener("focusout",a=>{k()&&!r.contains(a.relatedTarget)&&O()});const k=()=>(V==null?void 0:V.box)===r,M=a=>{k()&&!r.contains(a.target)&&O()},I=()=>{if(!k())return;const a=e();a?z(r,a):O()};setTimeout(()=>k()&&document.addEventListener("mousedown",M,!0)),window.addEventListener("scroll",I,!0),V={box:r,issue:o,teardown(){document.removeEventListener("mousedown",M,!0),window.removeEventListener("scroll",I,!0)}},u&&((L=E.querySelector("button"))==null||L.focus())}function O(){if(!V)return;const{box:e,teardown:o}=V;V=null,o(),e.remove()}function F(){return(V==null?void 0:V.issue)??null}function z(e,o){const g=e.offsetWidth,m=e.offsetHeight,d=o.bottom+6,r=d+m>window.innerHeight-8&&o.top-6-m>8?o.top-6-m:d;e.style.top=Math.round(r)+"px",e.style.left=Math.round(Math.max(8,Math.min(o.left,window.innerWidth-g-8)))+"px"}function re(e,o){if(o.path==null||o.offset==null||o.length==null)return null;let u=e,c=0;for(const d of String(o.path).split(".")){if(!/^\d+$/.test(d))return null;const r=Number(d);if(r>=u.childCount)return null;for(let f=0;f<r;f++)c+=u.child(f).nodeSize;u=u.child(r),c+=1}if(!u.isTextblock)return null;const g=Y(u,c,o.offset,1),m=Y(u,c,o.offset+o.length,-1);return g===null||m===null||m<=g?null:{from:g,to:m}}function Y(e,o,u,c){let g=0,m=o;for(let d=0;d<e.childCount;d++){const r=e.child(d),f=r.isText?r.text.length:r.type.name==="hardBreak"?1:0;if(f>0&&(u<g+f||c<0&&u===g+f))return m+(u-g);g+=f,m+=r.nodeSize}return u===g?m:null}function Q(e,o,u){const c=(e.match(/[ \t]+$/)??[""])[0].length,g=(o.match(/^[ \t]+/)??[""])[0].length;return u===""?{back:0,forward:c>0||e===""?g:0}:{back:/^[,.;:!?)\]]/.test(u)?c:0,forward:/[ \t]$/.test(u)?g:0}}const{unref:H,watch:ae}=window.Vue,le="readability_panel",q="copydesk_ignores";function B(e){var u;const o=(u=H(e==null?void 0:e.values))==null?void 0:u[q];return Array.isArray(o)?o.filter(c=>typeof c=="string"):[]}function ce(e,o){o&&e.setFieldValue(q,[...new Set([...B(e),o])])}function ue(e){e.setFieldValue(q,[])}function X(e,{handle:o,fieldPathPrefix:u,config:c},g){const m=Z(H(e==null?void 0:e.blueprint));if(!m)return null;const d=(u||o||"").split("."),r=d.length>1;return!(m.prose_fields??[]).includes(d[0])||r&&g==="text"?null:{field:d[0],key:[u,o].filter(Boolean).join(".")||o,label:(c==null?void 0:c.display)||o,collection:m.collection??null,ignores:()=>B(e),ignore:f=>ce(e,f),onIgnoresChange:f=>ae(()=>B(e).join(`
`),f),request:()=>({reference:H(e.reference)||null,site:H(e.site)||null,collection:m.collection??null})}}function Z(e){if(!e||typeof e!="object")return null;if(e.handle===le)return e.config??e;for(const o of Object.values(e)){const u=Z(o);if(u)return u}return null}const de=e=>e.rule+"\0"+e.excerpt;function fe({bard:e,tiptap:o}){const u=X(e.publishContainer,e,"bard");if(!u)return[];const{Extension:c}=o.core,{Plugin:g,PluginKey:m,TextSelection:d}=o.pm.state,{Decoration:r,DecorationSet:f}=o.pm.view,x=new m("copydeskLint"),w=a=>new g({key:x,state:{init:()=>({set:f.empty,maps:[]}),apply(i,h){let{set:l,maps:n}=h;const s=i.getMeta(x);return i.docChanged&&(l=l.map(i.mapping,i.doc),n.push(i.mapping)),s!=null&&s.sent&&(n=[]),s!=null&&s.found&&(l=f.create(i.doc,s.found.map(({from:b,to:_,issue:t})=>r.inline(b,_,{class:"copydesk-issue copydesk-issue--"+R(t),"data-copydesk-rule":t.rule},{issue:t})))),s!=null&&s.remove&&(l=l.remove(l.find(void 0,void 0,b=>s.remove(b.issue)))),{set:l,maps:n}}},props:{decorations:i=>x.getState(i).set,handleClick(i,h){const l=E(i.state,h);return l&&k(i,l,!1),!1},handleKeyDown(i,h){if(!(h.altKey&&h.key==="Enter"))return!1;const l=E(i.state,i.state.selection.from);return l?(k(i,l,!0),!0):!1}},view(i){let h=null;const l=K({target:u,kind:"bard",read(){return i.dispatch(i.state.tr.setMeta(x,{sent:!0}).setMeta("addToHistory",!1)),{value:a.getJSON().content??[],doc:i.state.doc}},onResult(s,b){if(i.isDestroyed)return;const{maps:_}=x.getState(i.state),t=u.ignores(),p=[];for(const y of s.issues??[]){const S=t.includes(y.fingerprint)?null:re(b.doc,y);if(!S)continue;const C=_.reduce((P,T)=>T.map(P,1),S.from),j=_.reduce((P,T)=>T.map(P,-1),S.to);C<j&&p.push({from:C,to:j,issue:y})}i.dispatch(i.state.tr.setMeta(x,{found:p}).setMeta("addToHistory",!1)),L(i)}});i.editable&&l.schedule();const n=u.onIgnoresChange(()=>l.schedule());return{update(s,b){!s.state.doc.eq(b.doc)&&s.editable&&l.schedule();const{selection:_}=s.state,t=_.empty?E(s.state,_.from):null,p=t?de(t.issue):null;p&&p!==h&&ee(W[R(t.issue)].word+": "+t.issue.message+" "+ne+" for choices."),h=p;const y=F();y&&!v(s.state,y)&&s.hasFocus()&&O()},destroy(){l.stop(),n(),G(u.key),v(i.state,F())&&O()}}}});function E(a,i){const h=x.getState(a).set.find(i,i).filter(n=>n.from<=i&&i<=n.to);if(!h.length)return null;const l=h.reduce((n,s)=>s.to-s.from<n.to-n.from?s:n);return{from:l.from,to:l.to,issue:l.spec.issue}}function v(a,i){if(!i)return null;const h=n=>n===i||n.rule===i.rule&&n.excerpt===i.excerpt&&n.path===i.path&&n.offset===i.offset,l=x.getState(a).set.find(void 0,void 0,n=>h(n.issue))[0];return l?{from:l.from,to:l.to,issue:i}:null}function k(a,i,h){ie({anchor:()=>{const n=a.isDestroyed?null:v(a.state,i.issue);if(!n)return null;const s=a.coordsAtPos(n.from),b=a.coordsAtPos(n.to);return{left:s.left,top:Math.min(s.top,b.top),bottom:Math.max(s.bottom,b.bottom)}},issue:i.issue,focus:h,onReplace:n=>M(a,i.issue,n),onIgnore:()=>I(a,i.issue),onReturn:()=>a.focus()})}function M(a,i,h){const l=v(a.state,i);if(!l){a.focus();return}const{doc:n}=a.state,s=n.resolve(l.from),b=n.resolve(l.to),_=n.textBetween(s.start(),l.from,`
`,`
`),t=n.textBetween(l.to,b.end(),`
`,`
`),{back:p,forward:y}=Q(_,t,h),S=l.from-p,C=a.state.tr.insertText(h,S,l.to+y);C.setSelection(d.create(C.doc,S+h.length)),a.dispatch(C.scrollIntoView()),a.focus()}function I(a,i){u.ignore(i.fingerprint),a.dispatch(a.state.tr.setMeta(x,{remove:h=>h.fingerprint===i.fingerprint}).setMeta("addToHistory",!1)),a.focus(),L(a)}function L(a){const i=x.getState(a.state).set.find().sort((h,l)=>h.from-l.from).map(h=>({issue:h.spec.issue,reveal(){const l=v(a.state,h.spec.issue);return l?(a.dispatch(a.state.tr.setSelection(d.create(a.state.doc,l.from,l.to)).scrollIntoView()),a.focus(),requestAnimationFrame(()=>k(a,l,!1)),!0):!1}}));U(u.key,{label:u.label,issues:i})}return c.create({name:"copydeskLint",addProseMirrorPlugins(){return[w(this.editor)]}})}const{h:A,ref:pe,shallowRef:ge,defineComponent:me,onMounted:he,onUpdated:ye,onBeforeUnmount:ve}=window.Vue;function ke(e,o){return me({name:"CopydeskIssueList",inheritAttrs:!1,setup(u,{attrs:c,expose:g}){const m=pe(null);g(new Proxy({},{get:(n,s)=>{var b;return(b=m.value)==null?void 0:b[s]},has:(n,s)=>m.value!=null&&s in m.value}));let d=null;try{d=window.__STATAMIC__.ui.injectPublishContext()}catch{d=null}const r=X(d,{handle:c.handle,fieldPathPrefix:c.fieldPathPrefix,config:c.config},o);if(!r)return()=>A(e,{...c,ref:m});const f=ge({issues:[],value:null});let x=c.value;const w=K({target:r,kind:o,read:()=>({value:typeof c.value=="string"?c.value:""}),onResult(n,s){f.value={issues:n.issues??[],value:s.value},h()}});he(()=>w.schedule()),ye(()=>{c.value!==x&&(x=c.value,w.schedule())});const E=r.onIgnoresChange(()=>h());ve(()=>{w.stop(),E(),G(r.key)});const v=()=>{const n=r.ignores();return f.value.issues.filter(s=>!n.includes(s.fingerprint))},k=n=>n.offset!=null&&o!=="markdown",M=()=>c.id?document.getElementById(c.id):null,I=n=>n&&HTMLElement.prototype.focus.call(n);function L(n,s){var P;const b=typeof c.value=="string"?c.value:"";if(b!==f.value.value){w.now();return}const _=b.replace(/\r\n?/g,`
`),t=n.offset,p=n.offset+n.length,{back:y,forward:S}=Q(_.slice(0,t),_.slice(p),s),C=_.slice(0,t-y)+s+_.slice(p+S),j=t-y+s.length;(P=c["onUpdate:value"])==null||P.call(c,C),f.value={issues:[],value:null},requestAnimationFrame(()=>{var $;const T=M();I(T),($=T==null?void 0:T.setSelectionRange)==null||$.call(T,j,j)})}function a(n){r.ignore(n.fingerprint),h()}function i(n){var b,_;const s=M();return s&&k(n)?(I(s),(b=s.setSelectionRange)==null||b.call(s,n.offset,n.offset+n.length)):((_=document.querySelector('[data-copydesk-list="'+CSS.escape(r.key)+'"]'))==null||_.scrollIntoView({block:"center"}),I(s)),!0}function h(){U(r.key,{label:r.label,issues:v().map(n=>({issue:n,reveal:()=>i(n)}))})}function l(){const n=v();return n.length?A("div",{class:"copydesk-field-issues","data-copydesk-list":r.key},[A("p",{class:"copydesk-sr-only"},n.length===1?"Copydesk found 1 thing in this field.":"Copydesk found "+n.length+" things in this field."),A("ul",{class:"copydesk-field-issues__list"},n.map((s,b)=>{const _=R(s);return A("li",{key:b,class:"copydesk-field-issue","data-severity":_},[A("span",{class:"copydesk-field-issue__kind"},W[_].word)," ",A("span",{class:"copydesk-field-issue__message"},s.message),s.excerpt?[" ",A("mark",{class:"copydesk-field-issue__excerpt"},s.excerpt)]:null,A("span",{class:"copydesk-field-issue__actions"},[...k(s)?(s.suggestions??[]).map(t=>A("button",{type:"button",class:"copydesk-popover__button copydesk-popover__button--primary",onClick:()=>L(s,t)},J(t))):(s.suggestions??[]).length?[A("span",{class:"copydesk-field-issue__try"},"Try: "+s.suggestions.map(t=>t.trim()||"leaving it out").join(", "))]:[],A("button",{type:"button",class:"copydesk-popover__button",onClick:()=>a(s)},"Ignore here")])])}))]):null}return()=>[A(e,{...c,ref:m}),l()]}})}Statamic.$bard.addExtension(fe);Statamic.configuring(()=>{const e=Statamic.$app;for(const[o,u]of[["markdown-fieldtype","markdown"],["textarea-fieldtype","textarea"],["text-fieldtype","text"]]){const c=e.component(o);c&&e.component(o,ke(c,u))}});(function(){const{injectPublishContext:e}=window.__STATAMIC__.ui,o=[{key:"error",color:"red",one:"1 to fix",many:d=>d+" to fix"},{key:"warning",color:"amber",one:"1 to look at",many:d=>d+" to look at"},{key:"tip",color:"gray",one:"1 tip",many:d=>d+" tips"}],u={on_target:{color:"emerald",words:"on target"},above:{color:"red",words:"harder than your target"},below:{color:"emerald",words:"easier than your target, which is fine"}};function c(d){return String(d).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;")}const g=70;function m(d){if(!d)return"";const r=Array.from(d.text);let f=r.slice(0,d.offset).join("");const x=r.slice(d.offset,d.offset+d.length).join("");let w=r.slice(d.offset+d.length).join("");return r.length>2*g&&d.length<r.length&&(f.length>g&&(f="…"+f.slice(-g).replace(/^\S*\s/,"")),w.length>g&&(w=w.slice(0,g).replace(/\s\S*$/,"")+"…")),c(f)+"<mark>"+c(x)+"</mark>"+c(w)}Statamic.$components.register("readability_panel-fieldtype",{props:["config","meta"],setup(d){const r=e(),f=Vue.reactive({checking:!1,result:null,failed:null,notYet:!1});async function x(){var p,y,S,C,j,P,T;f.checking=!0,f.failed=null,f.notYet=!1;const t=Statamic.$app.config.globalProperties.$axios;try{const{data:$}=await t.post(cp_url("copydesk/check"),{reference:(p=r.reference)==null?void 0:p.value,values:r.values.value,site:(y=r.site)==null?void 0:y.value,collection:(S=d.config)==null?void 0:S.collection,blueprint:(C=d.config)==null?void 0:C.blueprint});f.result=$}catch($){f.notYet=((j=$.response)==null?void 0:j.status)===422,f.failed=((T=(P=$.response)==null?void 0:P.data)==null?void 0:T.message)||"The check could not be run.",f.result=null}finally{f.checking=!1}}const w=Vue.computed(()=>{var p;const t=(((p=r.errors)==null?void 0:p.value)??{}).copydesk;return Array.isArray(t)?t:[]});Vue.watch(w,t=>{t.length>0&&(f.result=null,f.failed=null)});const E=Vue.computed(()=>w.value.length>0&&!f.result&&!f.failed&&!f.checking),v=Vue.reactive({open:!1,reason:"",sending:!1,done:null,failed:null});async function k(){var t,p,y,S;v.sending=!0,v.failed=null;try{const{data:C}=await Statamic.$app.config.globalProperties.$axios.post(cp_url("copydesk/override"),{reference:(t=r.reference)==null?void 0:t.value,collection:(p=d.config)==null?void 0:p.collection,reason:v.reason});v.done=C.message,v.open=!1}catch(C){v.failed=((S=(y=C.response)==null?void 0:y.data)==null?void 0:S.message)||"The pass could not be given."}finally{v.sending=!1}}Vue.watch(w,()=>{v.done=null,v.failed=null});const M=Vue.computed(()=>{const t=f.result;if(!t||!t.band)return null;const p=u[t.comparison]??u.on_target;return{color:p.color,text:"Your target is "+t.target.label+". This page reads "+p.words+"."}}),I=Vue.computed(()=>{var p;const t=((p=f.result)==null?void 0:p.findings)??[];return o.map(y=>{const S=t.filter(C=>C.severity===y.key);return{key:y.key,color:y.color,items:S,badge:S.length===1?y.one:y.many(S.length)}}).filter(y=>y.items.length>0)}),L=Vue.computed(()=>{var t;return(((t=f.result)==null?void 0:t.sections)??[]).map(p=>{var y;return{...p,name:p.lead?"Before the first heading":p.title,color:p.band?((y=u[p.comparison])==null?void 0:y.color)??"gray":"gray",label:p.band?p.band.label:"Nothing to grade"}})});function a(t){return t.slice(0,5)}const i=Vue.computed(()=>Object.values(D.fields).flatMap(t=>t.issues.map(p=>({...p,label:t.label}))).sort((t,p)=>W[R(t.issue)].order-W[R(p.issue)].order)),h=Vue.computed(()=>o.map(t=>{const p=i.value.filter(y=>R(y.issue)===t.key||t.key==="tip"&&y.issue.severity==="info").length;return{key:t.key,color:t.color,n:p,badge:p===1?t.one:t.many(p)}}).filter(t=>t.n>0)),l=20,n=Vue.computed(()=>B(r).length);function s(t){return t===1?"1 thing is ignored on this page.":t+" things are ignored on this page."}function b(){ue(r)}function _(t){const p=Array.from(t.issue.excerpt||""),y=p.length>32?p.slice(0,30).join("").replace(/\s+\S*$/,"")+"…":p.join("");return"Show "+(y?"“"+y+"”":"it")+" in "+t.label}return{override:v,askToOverride:k,state:f,check:x,verdict:M,groups:I,sections:L,highlighted:m,shown:a,refusal:w,showRefusal:E,liveItems:i,liveCounts:h,LIVE_SHOWN:l,jumpLabel:_,ignoredCount:n,ignoredWords:s,stopIgnoring:b,severityWord:t=>W[R(t)].word}},template:`
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
        `})})();(function(){Statamic.$components.register("copydesk-scan-progress",{props:["url"],setup(e){const o=Vue.reactive({done:0,total:null});let u=null;async function c(){try{const{data:g}=await Statamic.$app.config.globalProperties.$axios.get(e.url);if(g.total===null){window.location.reload();return}o.done=g.done,o.total=g.total}catch{}u=setTimeout(c,2e3)}return Vue.onMounted(c),Vue.onBeforeUnmount(()=>clearTimeout(u)),{state:o}},template:`
            <div class="space-y-2" role="status" aria-live="polite">
                <p class="text-sm">{{ state.total === null ? 'Starting the scan.' : 'Scanning: ' + state.done + ' of ' + state.total + ' pages done.' }}</p>
                <p class="text-xs">The report shows here when it is done. You can leave this page; the scan carries on.</p>
            </div>
        `})})();
