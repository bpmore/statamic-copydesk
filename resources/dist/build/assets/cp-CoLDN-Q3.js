let P=null;function te(s){P||(P=document.createElement("div"),P.className="copydesk-sr-only",P.setAttribute("aria-live","polite"),P.setAttribute("role","status"),document.body.appendChild(P)),P.textContent="",setTimeout(()=>{P.textContent=s},50)}const ne=/Mac|iPhone|iPad/.test(navigator.platform||navigator.userAgent),se=ne?"Option+Enter":"Alt+Enter",oe=600;function U({target:s,kind:l,read:d,onResult:a}){let g=null,p=null,i=!1;async function r(){var _;g=null,p==null||p.abort(),p=new AbortController;const k=d(),w=Statamic.$app.config.globalProperties.$axios;try{const{data:h}=await w.post(cp_url("copydesk/lint"),{...s.request(),field:s.field,kind:l,value:k.value},{signal:p.signal});a(h,k)}catch(h){if((h==null?void 0:h.code)==="ERR_CANCELED"||(h==null?void 0:h.name)==="CanceledError")return;[401,403,404,419].includes((_=h==null?void 0:h.response)==null?void 0:_.status)&&y()}}function c(){i||(clearTimeout(g),g=setTimeout(r,oe))}function y(){i=!0,clearTimeout(g),p==null||p.abort()}return{schedule:c,stop:y,now:r}}const{reactive:le}=window.Vue,W=le({fields:{}});function G(s,l){W.fields[s]=l}function J(s){delete W.fields[s]}const R={error:{word:"To fix",order:0},warning:{word:"To look at",order:1},tip:{word:"Tip",order:2},info:{word:"Tip",order:2}};function N(s){return R[s.severity]?s.severity:"warning"}function Q(s){const l=s.trim();return l===""?"Remove it":"Use “"+l+"”"}let L=null;function re({anchor:s,issue:l,focus:d,onReplace:a,onIgnore:g,onReturn:p}){var M;V();const i=N(l),r=document.createElement("div"),c="copydesk-popover-"+Math.random().toString(36).slice(2);r.className="copydesk-popover",r.dataset.severity=i,r.setAttribute("role","dialog"),r.setAttribute("aria-labelledby",c);const y=document.createElement("p");y.className="copydesk-popover__kind",y.textContent=R[i].word;const k=document.createElement("p");k.className="copydesk-popover__message",k.id=c,k.textContent=l.message;const w=document.createElement("div");w.className="copydesk-popover__actions";const _=(b,f,o=!1)=>{const n=document.createElement("button");return n.type="button",n.className="copydesk-popover__button"+(o?" copydesk-popover__button--primary":""),n.textContent=b,n.addEventListener("click",()=>{V(),f()}),w.appendChild(n),n};for(const b of l.suggestions??[])_(Q(b),()=>a(b),!0);_("Ignore here",g),_("Close",p),r.append(y,k,w),document.body.appendChild(r),F(r,s()),r.addEventListener("keydown",b=>{b.key==="Escape"&&(b.preventDefault(),b.stopPropagation(),V(),p())}),r.addEventListener("focusout",b=>{h()&&!r.contains(b.relatedTarget)&&V()});const h=()=>(L==null?void 0:L.box)===r,E=b=>{h()&&!r.contains(b.target)&&V()},A=()=>{if(!h())return;const b=s();b?F(r,b):V()};setTimeout(()=>h()&&document.addEventListener("mousedown",E,!0)),window.addEventListener("scroll",A,!0),L={box:r,issue:l,teardown(){document.removeEventListener("mousedown",E,!0),window.removeEventListener("scroll",A,!0)}},d&&((M=w.querySelector("button"))==null||M.focus())}function V(){if(!L)return;const{box:s,teardown:l}=L;L=null,l(),s.remove()}function q(){return(L==null?void 0:L.issue)??null}function F(s,l){const g=s.offsetWidth,p=s.offsetHeight,i=l.bottom+6,r=i+p>window.innerHeight-8&&l.top-6-p>8?l.top-6-p:i;s.style.top=Math.round(r)+"px",s.style.left=Math.round(Math.max(8,Math.min(l.left,window.innerWidth-g-8)))+"px"}function ie(s,l){if(l.path==null||l.offset==null||l.length==null)return null;let d=s,a=0;for(const i of String(l.path).split(".")){if(!/^\d+$/.test(i))return null;const r=Number(i);if(r>=d.childCount)return null;for(let c=0;c<r;c++)a+=d.child(c).nodeSize;d=d.child(r),a+=1}if(!d.isTextblock)return null;const g=K(d,a,l.offset,1),p=K(d,a,l.offset+l.length,-1);return g===null||p===null||p<=g?null:{from:g,to:p}}function K(s,l,d,a){let g=0,p=l;for(let i=0;i<s.childCount;i++){const r=s.child(i),c=r.isText?r.text.length:r.type.name==="hardBreak"?1:0;if(c>0&&(d<g+c||a<0&&d===g+c))return p+(d-g);g+=c,p+=r.nodeSize}return d===g?p:null}function X(s,l,d){const a=(s.match(/[ \t]+$/)??[""])[0].length,g=(l.match(/^[ \t]+/)??[""])[0].length;return d===""?{back:0,forward:a>0||s===""?g:0}:{back:/^[,.;:!?)\]]/.test(d)?a:0,forward:/[ \t]$/.test(d)?g:0}}const{unref:D}=window.Vue,ae="readability_panel";function Z(s,{handle:l,fieldPathPrefix:d,config:a},g){const p=ee(D(s==null?void 0:s.blueprint));if(!p)return null;const i=(d||l||"").split("."),r=i.length>1;return!(p.prose_fields??[]).includes(i[0])||r&&g==="text"?null:{field:i[0],key:[d,l].filter(Boolean).join(".")||l,label:(a==null?void 0:a.display)||l,collection:p.collection??null,request:()=>({reference:D(s.reference)||null,site:D(s.site)||null,collection:p.collection??null})}}function ee(s){if(!s||typeof s!="object")return null;if(s.handle===ae)return s.config??s;for(const l of Object.values(s)){const d=ee(l);if(d)return d}return null}const H=s=>s.rule+"\0"+s.excerpt;function ce({bard:s,tiptap:l}){const d=Z(s.publishContainer,s,"bard");if(!d)return[];const{Extension:a}=l.core,{Plugin:g,PluginKey:p,TextSelection:i}=l.pm.state,{Decoration:r,DecorationSet:c}=l.pm.view,y=new p("copydeskLint"),k=new Set,w=f=>new g({key:y,state:{init:()=>({set:c.empty,maps:[]}),apply(o,n){let{set:e,maps:t}=n;const u=o.getMeta(y);return o.docChanged&&(e=e.map(o.mapping,o.doc),t.push(o.mapping)),u!=null&&u.sent&&(t=[]),u!=null&&u.found&&(e=c.create(o.doc,u.found.map(({from:m,to:v,issue:x})=>r.inline(m,v,{class:"copydesk-issue copydesk-issue--"+N(x),"data-copydesk-rule":x.rule},{issue:x})))),u!=null&&u.remove&&(e=e.remove(e.find(void 0,void 0,m=>u.remove(m.issue)))),{set:e,maps:t}}},props:{decorations:o=>y.getState(o).set,handleClick(o,n){const e=_(o.state,n);return e&&E(o,e,!1),!1},handleKeyDown(o,n){if(!(n.altKey&&n.key==="Enter"))return!1;const e=_(o.state,o.state.selection.from);return e?(E(o,e,!0),!0):!1}},view(o){let n=null;const e=U({target:d,kind:"bard",read(){return o.dispatch(o.state.tr.setMeta(y,{sent:!0}).setMeta("addToHistory",!1)),{value:f.getJSON().content??[],doc:o.state.doc}},onResult(t,u){if(o.isDestroyed)return;const{maps:m}=y.getState(o.state),v=[];for(const x of t.issues??[]){const S=k.has(H(x))?null:ie(u.doc,x);if(!S)continue;const T=m.reduce((I,O)=>O.map(I,1),S.from),j=m.reduce((I,O)=>O.map(I,-1),S.to);T<j&&v.push({from:T,to:j,issue:x})}o.dispatch(o.state.tr.setMeta(y,{found:v}).setMeta("addToHistory",!1)),b(o)}});return o.editable&&e.schedule(),{update(t,u){!t.state.doc.eq(u.doc)&&t.editable&&e.schedule();const{selection:m}=t.state,v=m.empty?_(t.state,m.from):null,x=v?H(v.issue):null;x&&x!==n&&te(R[N(v.issue)].word+": "+v.issue.message+" "+se+" for choices."),n=x;const S=q();S&&!h(t.state,S)&&t.hasFocus()&&V()},destroy(){e.stop(),J(d.key),h(o.state,q())&&V()}}}});function _(f,o){const n=y.getState(f).set.find(o,o).filter(t=>t.from<=o&&o<=t.to);if(!n.length)return null;const e=n.reduce((t,u)=>u.to-u.from<t.to-t.from?u:t);return{from:e.from,to:e.to,issue:e.spec.issue}}function h(f,o){if(!o)return null;const n=t=>t===o||t.rule===o.rule&&t.excerpt===o.excerpt&&t.path===o.path&&t.offset===o.offset,e=y.getState(f).set.find(void 0,void 0,t=>n(t.issue))[0];return e?{from:e.from,to:e.to,issue:o}:null}function E(f,o,n){re({anchor:()=>{const t=f.isDestroyed?null:h(f.state,o.issue);if(!t)return null;const u=f.coordsAtPos(t.from),m=f.coordsAtPos(t.to);return{left:u.left,top:Math.min(u.top,m.top),bottom:Math.max(u.bottom,m.bottom)}},issue:o.issue,focus:n,onReplace:t=>A(f,o.issue,t),onIgnore:()=>M(f,o.issue),onReturn:()=>f.focus()})}function A(f,o,n){const e=h(f.state,o);if(!e){f.focus();return}const{doc:t}=f.state,u=t.resolve(e.from),m=t.resolve(e.to),v=t.textBetween(u.start(),e.from,`
`,`
`),x=t.textBetween(e.to,m.end(),`
`,`
`),{back:S,forward:T}=X(v,x,n),j=e.from-S,I=f.state.tr.insertText(n,j,e.to+T);I.setSelection(i.create(I.doc,j+n.length)),f.dispatch(I.scrollIntoView()),f.focus()}function M(f,o){const n=H(o);k.add(n),f.dispatch(f.state.tr.setMeta(y,{remove:e=>H(e)===n}).setMeta("addToHistory",!1)),f.focus(),b(f)}function b(f){const o=y.getState(f.state).set.find().sort((n,e)=>n.from-e.from).map(n=>({issue:n.spec.issue,reveal(){const e=h(f.state,n.spec.issue);return e?(f.dispatch(f.state.tr.setSelection(i.create(f.state.doc,e.from,e.to)).scrollIntoView()),f.focus(),requestAnimationFrame(()=>E(f,e,!1)),!0):!1}}));G(d.key,{label:d.label,issues:o})}return a.create({name:"copydeskLint",addProseMirrorPlugins(){return[w(this.editor)]}})}const{h:C,ref:Y,shallowRef:ue,defineComponent:de,onMounted:fe,onUpdated:pe,onBeforeUnmount:ge}=window.Vue,z=s=>s.rule+"\0"+s.excerpt;function me(s,l){return de({name:"CopydeskIssueList",inheritAttrs:!1,setup(d,{attrs:a,expose:g}){const p=Y(null);g(new Proxy({},{get:(e,t)=>{var u;return(u=p.value)==null?void 0:u[t]},has:(e,t)=>p.value!=null&&t in p.value}));let i=null;try{i=window.__STATAMIC__.ui.injectPublishContext()}catch{i=null}const r=Z(i,{handle:a.handle,fieldPathPrefix:a.fieldPathPrefix,config:a.config},l);if(!r)return()=>C(s,{...a,ref:p});const c=ue({issues:[],value:null}),y=Y(new Set);let k=a.value;const w=U({target:r,kind:l,read:()=>({value:typeof a.value=="string"?a.value:""}),onResult(e,t){c.value={issues:e.issues??[],value:t.value},o()}});fe(()=>w.schedule()),pe(()=>{a.value!==k&&(k=a.value,w.schedule())}),ge(()=>{w.stop(),J(r.key)});const _=()=>c.value.issues.filter(e=>!y.value.has(z(e))),h=e=>e.offset!=null&&l!=="markdown",E=()=>a.id?document.getElementById(a.id):null,A=e=>e&&HTMLElement.prototype.focus.call(e);function M(e,t){var O;const u=typeof a.value=="string"?a.value:"";if(u!==c.value.value){w.now();return}const m=u.replace(/\r\n?/g,`
`),v=e.offset,x=e.offset+e.length,{back:S,forward:T}=X(m.slice(0,v),m.slice(x),t),j=m.slice(0,v-S)+t+m.slice(x+T),I=v-S+t.length;(O=a["onUpdate:value"])==null||O.call(a,j),c.value={issues:[],value:null},requestAnimationFrame(()=>{var B;const $=E();A($),(B=$==null?void 0:$.setSelectionRange)==null||B.call($,I,I)})}function b(e){y.value=new Set([...y.value,z(e)]),o()}function f(e){var u,m;const t=E();return t&&h(e)?(A(t),(u=t.setSelectionRange)==null||u.call(t,e.offset,e.offset+e.length)):((m=document.querySelector('[data-copydesk-list="'+CSS.escape(r.key)+'"]'))==null||m.scrollIntoView({block:"center"}),A(t)),!0}function o(){G(r.key,{label:r.label,issues:_().map(e=>({issue:e,reveal:()=>f(e)}))})}function n(){const e=_();return e.length?C("div",{class:"copydesk-field-issues","data-copydesk-list":r.key},[C("p",{class:"copydesk-sr-only"},e.length===1?"Copydesk found 1 thing in this field.":"Copydesk found "+e.length+" things in this field."),C("ul",{class:"copydesk-field-issues__list"},e.map((t,u)=>{const m=N(t);return C("li",{key:u,class:"copydesk-field-issue","data-severity":m},[C("span",{class:"copydesk-field-issue__kind"},R[m].word)," ",C("span",{class:"copydesk-field-issue__message"},t.message),t.excerpt?[" ",C("mark",{class:"copydesk-field-issue__excerpt"},t.excerpt)]:null,C("span",{class:"copydesk-field-issue__actions"},[...h(t)?(t.suggestions??[]).map(v=>C("button",{type:"button",class:"copydesk-popover__button copydesk-popover__button--primary",onClick:()=>M(t,v)},Q(v))):(t.suggestions??[]).length?[C("span",{class:"copydesk-field-issue__try"},"Try: "+t.suggestions.map(v=>v.trim()||"leaving it out").join(", "))]:[],C("button",{type:"button",class:"copydesk-popover__button",onClick:()=>b(t)},"Ignore here")])])}))]):null}return()=>[C(s,{...a,ref:p}),n()]}})}Statamic.$bard.addExtension(ce);Statamic.configuring(()=>{const s=Statamic.$app;for(const[l,d]of[["markdown-fieldtype","markdown"],["textarea-fieldtype","textarea"],["text-fieldtype","text"]]){const a=s.component(l);a&&s.component(l,me(a,d))}});(function(){const{injectPublishContext:s}=window.__STATAMIC__.ui,l=[{key:"error",color:"red",one:"1 to fix",many:i=>i+" to fix"},{key:"warning",color:"amber",one:"1 to look at",many:i=>i+" to look at"},{key:"tip",color:"gray",one:"1 tip",many:i=>i+" tips"}],d={on_target:{color:"emerald",words:"on target"},above:{color:"red",words:"harder than your target"},below:{color:"emerald",words:"easier than your target, which is fine"}};function a(i){return String(i).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;")}const g=70;function p(i){if(!i)return"";const r=Array.from(i.text);let c=r.slice(0,i.offset).join("");const y=r.slice(i.offset,i.offset+i.length).join("");let k=r.slice(i.offset+i.length).join("");return r.length>2*g&&i.length<r.length&&(c.length>g&&(c="…"+c.slice(-g).replace(/^\S*\s/,"")),k.length>g&&(k=k.slice(0,g).replace(/\s\S*$/,"")+"…")),a(c)+"<mark>"+a(y)+"</mark>"+a(k)}Statamic.$components.register("readability_panel-fieldtype",{props:["config","meta"],setup(i){const r=s(),c=Vue.reactive({checking:!1,result:null,failed:null,notYet:!1});async function y(){var e,t,u,m,v,x,S;c.checking=!0,c.failed=null,c.notYet=!1;const n=Statamic.$app.config.globalProperties.$axios;try{const{data:T}=await n.post(cp_url("copydesk/check"),{reference:(e=r.reference)==null?void 0:e.value,values:r.values.value,site:(t=r.site)==null?void 0:t.value,collection:(u=i.config)==null?void 0:u.collection,blueprint:(m=i.config)==null?void 0:m.blueprint});c.result=T}catch(T){c.notYet=((v=T.response)==null?void 0:v.status)===422,c.failed=((S=(x=T.response)==null?void 0:x.data)==null?void 0:S.message)||"The check could not be run.",c.result=null}finally{c.checking=!1}}const k=Vue.computed(()=>{var e;const n=(((e=r.errors)==null?void 0:e.value)??{}).copydesk;return Array.isArray(n)?n:[]});Vue.watch(k,n=>{n.length>0&&(c.result=null,c.failed=null)});const w=Vue.computed(()=>k.value.length>0&&!c.result&&!c.failed&&!c.checking),_=Vue.computed(()=>{const n=c.result;if(!n||!n.band)return null;const e=d[n.comparison]??d.on_target;return{color:e.color,text:"Your target is "+n.target.label+". This page reads "+e.words+"."}}),h=Vue.computed(()=>{var e;const n=((e=c.result)==null?void 0:e.findings)??[];return l.map(t=>{const u=n.filter(m=>m.severity===t.key);return{key:t.key,color:t.color,items:u,badge:u.length===1?t.one:t.many(u.length)}}).filter(t=>t.items.length>0)}),E=Vue.computed(()=>{var n;return(((n=c.result)==null?void 0:n.sections)??[]).map(e=>{var t;return{...e,name:e.lead?"Before the first heading":e.title,color:e.band?((t=d[e.comparison])==null?void 0:t.color)??"gray":"gray",label:e.band?e.band.label:"Nothing to grade"}})});function A(n){return n.slice(0,5)}const M=Vue.computed(()=>Object.values(W.fields).flatMap(n=>n.issues.map(e=>({...e,label:n.label}))).sort((n,e)=>R[N(n.issue)].order-R[N(e.issue)].order)),b=Vue.computed(()=>l.map(n=>{const e=M.value.filter(t=>N(t.issue)===n.key||n.key==="tip"&&t.issue.severity==="info").length;return{key:n.key,color:n.color,n:e,badge:e===1?n.one:n.many(e)}}).filter(n=>n.n>0)),f=20;function o(n){return"Show "+(n.issue.excerpt?"“"+n.issue.excerpt+"”":"it")+" in "+n.label}return{state:c,check:y,verdict:_,groups:h,sections:E,highlighted:p,shown:A,refusal:k,showRefusal:w,liveItems:M,liveCounts:b,LIVE_SHOWN:f,jumpLabel:o,severityWord:n=>R[N(n)].word}},template:`
            <div class="space-y-3">
                <div v-if="liveItems.length" class="space-y-2">
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
