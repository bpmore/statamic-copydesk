let $=null;function te(e){$||($=document.createElement("div"),$.className="copydesk-sr-only",$.setAttribute("aria-live","polite"),$.setAttribute("role","status"),document.body.appendChild($)),$.textContent="",setTimeout(()=>{$.textContent=e},50)}const ne=/Mac|iPhone|iPad/.test(navigator.platform||navigator.userAgent),se=ne?"Option+Enter":"Alt+Enter",oe=600;function K({target:e,kind:n,read:d,onResult:l}){let p=null,g=null,c=!1;async function i(){var v;p=null,g==null||g.abort(),g=new AbortController;const b=d(),T=Statamic.$app.config.globalProperties.$axios;try{const{data:w}=await T.post(cp_url("copydesk/lint"),{...e.request(),field:e.field,kind:n,value:b.value},{signal:g.signal});l(w,b)}catch(w){if((w==null?void 0:w.code)==="ERR_CANCELED"||(w==null?void 0:w.name)==="CanceledError")return;[401,403,404,419].includes((v=w==null?void 0:w.response)==null?void 0:v.status)&&k()}}function f(){c||(clearTimeout(p),p=setTimeout(i,oe))}function k(){c=!0,clearTimeout(p),g==null||g.abort()}return{schedule:f,stop:k,now:i}}const{reactive:re}=window.Vue,F=re({fields:{}});function U(e,n){F.fields[e]=n}function G(e){delete F.fields[e]}const W={error:{word:"To fix",order:0},warning:{word:"To look at",order:1},tip:{word:"Tip",order:2},info:{word:"Tip",order:2}};function j(e){return W[e.severity]?e.severity:"warning"}function J(e){const n=e.trim();return n===""?"Remove it":"Use “"+n+"”"}let M=null;function ie({anchor:e,issue:n,focus:d,onReplace:l,onIgnore:p,onReturn:g}){var O;V();const c=j(n),i=document.createElement("div"),f="copydesk-popover-"+Math.random().toString(36).slice(2);i.className="copydesk-popover",i.dataset.severity=c,i.setAttribute("role","dialog"),i.setAttribute("aria-labelledby",f);const k=document.createElement("p");k.className="copydesk-popover__kind",k.textContent=W[c].word;const b=document.createElement("p");b.className="copydesk-popover__message",b.id=f,b.textContent=n.message;const T=document.createElement("div");T.className="copydesk-popover__actions";const v=(u,r,h=!1)=>{const a=document.createElement("button");return a.type="button",a.className="copydesk-popover__button"+(h?" copydesk-popover__button--primary":""),a.textContent=u,a.addEventListener("click",()=>{V(),r()}),T.appendChild(a),a};for(const u of n.suggestions??[])v(J(u),()=>l(u),!0);v("Ignore here",p),v("Close",g),i.append(k,b,T),document.body.appendChild(i),z(i,e()),i.addEventListener("keydown",u=>{u.key==="Escape"&&(u.preventDefault(),u.stopPropagation(),V(),g())}),i.addEventListener("focusout",u=>{w()&&!i.contains(u.relatedTarget)&&V()});const w=()=>(M==null?void 0:M.box)===i,P=u=>{w()&&!i.contains(u.target)&&V()},I=()=>{if(!w())return;const u=e();u?z(i,u):V()};setTimeout(()=>w()&&document.addEventListener("mousedown",P,!0)),window.addEventListener("scroll",I,!0),M={box:i,issue:n,teardown(){document.removeEventListener("mousedown",P,!0),window.removeEventListener("scroll",I,!0)}},d&&((O=T.querySelector("button"))==null||O.focus())}function V(){if(!M)return;const{box:e,teardown:n}=M;M=null,n(),e.remove()}function D(){return(M==null?void 0:M.issue)??null}function z(e,n){const p=e.offsetWidth,g=e.offsetHeight,c=n.bottom+6,i=c+g>window.innerHeight-8&&n.top-6-g>8?n.top-6-g:c;e.style.top=Math.round(i)+"px",e.style.left=Math.round(Math.max(8,Math.min(n.left,window.innerWidth-p-8)))+"px"}function ae(e,n){if(n.path==null||n.offset==null||n.length==null)return null;let d=e,l=0;for(const c of String(n.path).split(".")){if(!/^\d+$/.test(c))return null;const i=Number(c);if(i>=d.childCount)return null;for(let f=0;f<i;f++)l+=d.child(f).nodeSize;d=d.child(i),l+=1}if(!d.isTextblock)return null;const p=Y(d,l,n.offset,1),g=Y(d,l,n.offset+n.length,-1);return p===null||g===null||g<=p?null:{from:p,to:g}}function Y(e,n,d,l){let p=0,g=n;for(let c=0;c<e.childCount;c++){const i=e.child(c),f=i.isText?i.text.length:i.type.name==="hardBreak"?1:0;if(f>0&&(d<p+f||l<0&&d===p+f))return g+(d-p);p+=f,g+=i.nodeSize}return d===p?g:null}function X(e,n,d){const l=(e.match(/[ \t]+$/)??[""])[0].length,p=(n.match(/^[ \t]+/)??[""])[0].length;return d===""?{back:0,forward:l>0||e===""?p:0}:{back:/^[,.;:!?)\]]/.test(d)?l:0,forward:/[ \t]$/.test(d)?p:0}}function Q(e,n){if(!le(e)){n();return}requestAnimationFrame(()=>n())}function le(e,n=document){var p;const d=[];for(let g=e==null?void 0:e.closest('[role="tabpanel"]');g;g=(p=g.parentElement)==null?void 0:p.closest('[role="tabpanel"]'))d.unshift(g);let l=!1;for(const g of d){const c=g.id?n.querySelector('[role="tab"][aria-controls="'+CSS.escape(g.id)+'"]'):null;!c||c.getAttribute("aria-selected")==="true"||(c.dispatchEvent(new KeyboardEvent("keydown",{key:"Enter",bubbles:!0,cancelable:!0})),l=!0)}return l}const{unref:q,watch:ce}=window.Vue,ue="readability_panel",B="copydesk_ignores";function H(e){var d;const n=(d=q(e==null?void 0:e.values))==null?void 0:d[B];return Array.isArray(n)?n.filter(l=>typeof l=="string"):[]}function de(e,n){n&&e.setFieldValue(B,[...new Set([...H(e),n])])}function fe(e){e.setFieldValue(B,[])}function Z(e,{handle:n,fieldPathPrefix:d,config:l},p){const g=ee(q(e==null?void 0:e.blueprint));if(!g)return null;const c=(d||n||"").split("."),i=c.length>1;return!(g.prose_fields??[]).includes(c[0])||i&&p==="text"?null:{field:c[0],key:[d,n].filter(Boolean).join(".")||n,label:(l==null?void 0:l.display)||n,collection:g.collection??null,ignores:()=>H(e),ignore:f=>de(e,f),onIgnoresChange:f=>ce(()=>H(e).join(`
`),f),request:()=>({reference:q(e.reference)||null,site:q(e.site)||null,collection:g.collection??null})}}function ee(e){if(!e||typeof e!="object")return null;if(e.handle===ue)return e.config??e;for(const n of Object.values(e)){const d=ee(n);if(d)return d}return null}const pe=e=>e.rule+"\0"+e.excerpt;function ge({bard:e,tiptap:n}){const d=Z(e.publishContainer,e,"bard");if(!d)return[];const{Extension:l}=n.core,{Plugin:p,PluginKey:g,TextSelection:c}=n.pm.state,{Decoration:i,DecorationSet:f}=n.pm.view,k=new g("copydeskLint"),b=u=>new p({key:k,state:{init:()=>({set:f.empty,maps:[]}),apply(r,h){let{set:a,maps:t}=h;const s=r.getMeta(k);return r.docChanged&&(a=a.map(r.mapping,r.doc),t.push(r.mapping)),s!=null&&s.sent&&(t=[]),s!=null&&s.found&&(a=f.create(r.doc,s.found.map(({from:x,to:_,issue:o})=>i.inline(x,_,{class:"copydesk-issue copydesk-issue--"+j(o),"data-copydesk-rule":o.rule},{issue:o})))),s!=null&&s.remove&&(a=a.remove(a.find(void 0,void 0,x=>s.remove(x.issue)))),{set:a,maps:t}}},props:{decorations:r=>k.getState(r).set,handleClick(r,h){const a=T(r.state,h);return a&&w(r,a,!1),!1},handleKeyDown(r,h){if(h.key==="Escape"&&v(r.state,D()))return V(),!0;if(!(h.altKey&&h.key==="Enter"))return!1;const a=T(r.state,r.state.selection.from);return a?(w(r,a,!0),!0):!1},handleDOMEvents:{blur(r,h){var t;const a=h.relatedTarget;return a&&v(r.state,D())&&!((t=document.querySelector(".copydesk-popover"))!=null&&t.contains(a))&&V(),!1}}},view(r){let h=null;const a=K({target:d,kind:"bard",read(){return r.dispatch(r.state.tr.setMeta(k,{sent:!0}).setMeta("addToHistory",!1)),{value:u.getJSON().content??[],doc:r.state.doc}},onResult(s,x){if(r.isDestroyed)return;const{maps:_}=k.getState(r.state),o=d.ignores(),m=[];for(const y of s.issues??[]){const S=o.includes(y.fingerprint)?null:ae(x.doc,y);if(!S)continue;const C=_.reduce((L,E)=>E.map(L,1),S.from),N=_.reduce((L,E)=>E.map(L,-1),S.to);C<N&&m.push({from:C,to:N,issue:y})}r.dispatch(r.state.tr.setMeta(k,{found:m}).setMeta("addToHistory",!1)),O(r)}});r.editable&&a.schedule();const t=d.onIgnoresChange(()=>a.schedule());return{update(s,x){!s.state.doc.eq(x.doc)&&s.editable&&a.schedule();const{selection:_}=s.state,o=_.empty?T(s.state,_.from):null,m=o?pe(o.issue):null;m&&m!==h&&te(W[j(o.issue)].word+": "+o.issue.message+" "+se+" for choices."),h=m;const y=D();y&&!v(s.state,y)&&s.hasFocus()&&V()},destroy(){a.stop(),t(),G(d.key),v(r.state,D())&&V()}}}});function T(u,r){const h=k.getState(u).set.find(r,r).filter(t=>t.from<=r&&r<=t.to);if(!h.length)return null;const a=h.reduce((t,s)=>s.to-s.from<t.to-t.from?s:t);return{from:a.from,to:a.to,issue:a.spec.issue}}function v(u,r){if(!r)return null;const h=t=>t===r||t.rule===r.rule&&t.excerpt===r.excerpt&&t.path===r.path&&t.offset===r.offset,a=k.getState(u).set.find(void 0,void 0,t=>h(t.issue))[0];return a?{from:a.from,to:a.to,issue:r}:null}function w(u,r,h){ie({anchor:()=>{const t=u.isDestroyed?null:v(u.state,r.issue);if(!t)return null;const s=u.coordsAtPos(t.from),x=u.coordsAtPos(t.to);return{left:s.left,top:Math.min(s.top,x.top),bottom:Math.max(s.bottom,x.bottom)}},issue:r.issue,focus:h,onReplace:t=>P(u,r.issue,t),onIgnore:()=>I(u,r.issue),onReturn:()=>u.focus()})}function P(u,r,h){const a=v(u.state,r);if(!a){u.focus();return}const{doc:t}=u.state,s=t.resolve(a.from),x=t.resolve(a.to),_=t.textBetween(s.start(),a.from,`
`,`
`),o=t.textBetween(a.to,x.end(),`
`,`
`),{back:m,forward:y}=X(_,o,h),S=a.from-m,C=u.state.tr.insertText(h,S,a.to+y);C.setSelection(c.create(C.doc,S+h.length)),u.dispatch(C.scrollIntoView()),u.focus()}function I(u,r){d.ignore(r.fingerprint),u.dispatch(u.state.tr.setMeta(k,{remove:h=>h.fingerprint===r.fingerprint}).setMeta("addToHistory",!1)),u.focus(),O(u)}function O(u){const r=k.getState(u.state).set.find().sort((h,a)=>h.from-a.from).map(h=>({issue:h.spec.issue,reveal(){const a=v(u.state,h.spec.issue);return a?(Q(u.dom,()=>{var s;u.dispatch(u.state.tr.setSelection(c.create(u.state.doc,a.from,a.to))),u.focus();const{node:t}=u.domAtPos(a.from);(s=t.nodeType===Node.TEXT_NODE?t.parentElement:t)==null||s.scrollIntoView({block:"center"}),requestAnimationFrame(()=>w(u,a,!1))}),!0):!1}}));U(d.key,{label:d.label,issues:r})}return l.create({name:"copydeskLint",addProseMirrorPlugins(){return[b(this.editor)]}})}const{h:A,ref:me,shallowRef:he,defineComponent:ye,onMounted:ve,onUpdated:ke,onBeforeUnmount:be}=window.Vue;function xe(e,n){return ye({name:"CopydeskIssueList",inheritAttrs:!1,setup(d,{attrs:l,expose:p}){const g=me(null);p(new Proxy({},{get:(t,s)=>{var x;return(x=g.value)==null?void 0:x[s]},has:(t,s)=>g.value!=null&&s in g.value}));let c=null;try{c=window.__STATAMIC__.ui.injectPublishContext()}catch{c=null}const i=Z(c,{handle:l.handle,fieldPathPrefix:l.fieldPathPrefix,config:l.config},n);if(!i)return()=>A(e,{...l,ref:g});const f=he({issues:[],value:null});let k=l.value;const b=K({target:i,kind:n,read:()=>({value:typeof l.value=="string"?l.value:""}),onResult(t,s){f.value={issues:t.issues??[],value:s.value},h()}});ve(()=>b.schedule()),ke(()=>{l.value!==k&&(k=l.value,b.schedule())});const T=i.onIgnoresChange(()=>h());be(()=>{b.stop(),T(),G(i.key)});const v=()=>{const t=i.ignores();return f.value.issues.filter(s=>!t.includes(s.fingerprint))},w=t=>t.offset!=null&&n!=="markdown",P=()=>l.id?document.getElementById(l.id):null,I=t=>t&&HTMLElement.prototype.focus.call(t);function O(t,s){var L;const x=typeof l.value=="string"?l.value:"";if(x!==f.value.value){b.now();return}const _=x.replace(/\r\n?/g,`
`),o=t.offset,m=t.offset+t.length,{back:y,forward:S}=X(_.slice(0,o),_.slice(m),s),C=_.slice(0,o-y)+s+_.slice(m+S),N=o-y+s.length;(L=l["onUpdate:value"])==null||L.call(l,C),f.value={issues:[],value:null},requestAnimationFrame(()=>{var R;const E=P();I(E),(R=E==null?void 0:E.setSelectionRange)==null||R.call(E,N,N)})}function u(t){i.ignore(t.fingerprint),h()}function r(t){const s=P(),x=document.querySelector('[data-copydesk-list="'+CSS.escape(i.key)+'"]');return Q(s??x,()=>{var _;s&&w(t)?(I(s),(_=s.setSelectionRange)==null||_.call(s,t.offset,t.offset+t.length)):(x==null||x.scrollIntoView({block:"center"}),I(s))}),!0}function h(){U(i.key,{label:i.label,issues:v().map(t=>({issue:t,reveal:()=>r(t)}))})}function a(){const t=v();return t.length?A("div",{class:"copydesk-field-issues","data-copydesk-list":i.key},[A("p",{class:"copydesk-sr-only"},t.length===1?"Copydesk found 1 thing in this field.":"Copydesk found "+t.length+" things in this field."),A("ul",{class:"copydesk-field-issues__list"},t.map((s,x)=>{const _=j(s);return A("li",{key:x,class:"copydesk-field-issue","data-severity":_},[A("span",{class:"copydesk-field-issue__kind"},W[_].word)," ",A("span",{class:"copydesk-field-issue__message"},s.message),s.excerpt?[" ",A("mark",{class:"copydesk-field-issue__excerpt"},s.excerpt)]:null,A("span",{class:"copydesk-field-issue__actions"},[...w(s)?(s.suggestions??[]).map(o=>A("button",{type:"button",class:"copydesk-popover__button copydesk-popover__button--primary",onClick:()=>O(s,o)},J(o))):(s.suggestions??[]).length?[A("span",{class:"copydesk-field-issue__try"},"Try: "+s.suggestions.map(o=>o.trim()||"leaving it out").join(", "))]:[],A("button",{type:"button",class:"copydesk-popover__button",onClick:()=>u(s)},"Ignore here")])])}))]):null}return()=>[A(e,{...l,ref:g}),a()]}})}Statamic.$bard.addExtension(ge);Statamic.configuring(()=>{const e=Statamic.$app;for(const[n,d]of[["markdown-fieldtype","markdown"],["textarea-fieldtype","textarea"],["text-fieldtype","text"]]){const l=e.component(n);l&&e.component(n,xe(l,d))}});(function(){const{injectPublishContext:e}=window.__STATAMIC__.ui,n=[{key:"error",color:"red",one:"1 to fix",many:c=>c+" to fix"},{key:"warning",color:"amber",one:"1 to look at",many:c=>c+" to look at"},{key:"tip",color:"gray",one:"1 tip",many:c=>c+" tips"}],d={on_target:{color:"emerald",words:"on target"},above:{color:"red",words:"harder than your target"},below:{color:"emerald",words:"easier than your target, which is fine"}};function l(c){return String(c).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;")}const p=70;function g(c){if(!c)return"";const i=Array.from(c.text);let f=i.slice(0,c.offset).join("");const k=i.slice(c.offset,c.offset+c.length).join("");let b=i.slice(c.offset+c.length).join("");return i.length>2*p&&c.length<i.length&&(f.length>p&&(f="…"+f.slice(-p).replace(/^\S*\s/,"")),b.length>p&&(b=b.slice(0,p).replace(/\s\S*$/,"")+"…")),l(f)+"<mark>"+l(k)+"</mark>"+l(b)}Statamic.$components.register("readability_panel-fieldtype",{props:["config","meta"],setup(c){const i=e(),f=Vue.reactive({checking:!1,result:null,failed:null,notYet:!1});async function k(){var m,y,S,C,N,L,E;f.checking=!0,f.failed=null,f.notYet=!1;const o=Statamic.$app.config.globalProperties.$axios;try{const{data:R}=await o.post(cp_url("copydesk/check"),{reference:(m=i.reference)==null?void 0:m.value,values:i.values.value,site:(y=i.site)==null?void 0:y.value,collection:(S=c.config)==null?void 0:S.collection,blueprint:(C=c.config)==null?void 0:C.blueprint});f.result=R}catch(R){f.notYet=((N=R.response)==null?void 0:N.status)===422,f.failed=((E=(L=R.response)==null?void 0:L.data)==null?void 0:E.message)||"The check could not be run.",f.result=null}finally{f.checking=!1}}const b=Vue.computed(()=>{var m;const o=(((m=i.errors)==null?void 0:m.value)??{}).copydesk;return Array.isArray(o)?o:[]});Vue.watch(b,o=>{o.length>0&&(f.result=null,f.failed=null)});const T=Vue.computed(()=>b.value.length>0&&!f.result&&!f.failed&&!f.checking),v=Vue.reactive({open:!1,reason:"",sending:!1,done:null,failed:null});async function w(){var o,m,y,S;v.sending=!0,v.failed=null;try{const{data:C}=await Statamic.$app.config.globalProperties.$axios.post(cp_url("copydesk/override"),{reference:(o=i.reference)==null?void 0:o.value,collection:(m=c.config)==null?void 0:m.collection,reason:v.reason});v.done=C.message,v.open=!1}catch(C){v.failed=((S=(y=C.response)==null?void 0:y.data)==null?void 0:S.message)||"The pass could not be given."}finally{v.sending=!1}}Vue.watch(b,()=>{v.done=null,v.failed=null});const P=Vue.computed(()=>{const o=f.result;if(!o||!o.band)return null;const m=d[o.comparison]??d.on_target;return{color:m.color,text:"Your target is "+o.target.label+". This page reads "+m.words+"."}}),I=Vue.computed(()=>{var m;const o=((m=f.result)==null?void 0:m.findings)??[];return n.map(y=>{const S=o.filter(C=>C.severity===y.key);return{key:y.key,color:y.color,items:S,badge:S.length===1?y.one:y.many(S.length)}}).filter(y=>y.items.length>0)}),O=Vue.computed(()=>{var o;return(((o=f.result)==null?void 0:o.sections)??[]).map(m=>{var y;return{...m,name:m.lead?"Before the first heading":m.title,color:m.band?((y=d[m.comparison])==null?void 0:y.color)??"gray":"gray",label:m.band?m.band.label:"Nothing to grade"}})});function u(o){return o.slice(0,5)}const r=Vue.computed(()=>Object.values(F.fields).flatMap(o=>o.issues.map(m=>({...m,label:o.label}))).sort((o,m)=>W[j(o.issue)].order-W[j(m.issue)].order)),h=Vue.computed(()=>n.map(o=>{const m=r.value.filter(y=>j(y.issue)===o.key||o.key==="tip"&&y.issue.severity==="info").length;return{key:o.key,color:o.color,n:m,badge:m===1?o.one:o.many(m)}}).filter(o=>o.n>0)),a=20,t=Vue.computed(()=>H(i).length);function s(o){return o===1?"1 thing is ignored on this page.":o+" things are ignored on this page."}function x(){fe(i)}function _(o){const m=Array.from(o.issue.excerpt||""),y=m.length>32?m.slice(0,30).join("").replace(/\s+\S*$/,"")+"…":m.join("");return"Show "+(y?"“"+y+"”":"it")+" in "+o.label}return{override:v,askToOverride:w,state:f,check:k,verdict:P,groups:I,sections:O,highlighted:g,shown:u,refusal:b,showRefusal:T,liveItems:r,liveCounts:h,LIVE_SHOWN:a,jumpLabel:_,ignoredCount:t,ignoredWords:s,stopIgnoring:x,severityWord:o=>W[j(o)].word}},template:`
            <div class="space-y-3">
                <div v-if="liveItems.length || ignoredCount" class="space-y-2">
                    <ui-heading size="sm" text="As you type" />
                    <div class="flex flex-wrap gap-2">
                        <div v-for="c in liveCounts" :key="c.key"><ui-badge :color="c.color" :text="c.badge" /></div>
                    </div>
                    <ul class="space-y-2">
                        <li v-for="(item, i) in liveItems.slice(0, LIVE_SHOWN)" :key="'l' + i" class="text-xs">
                            <div><button type="button" class="copydesk-jump" @click="item.reveal()">{{ jumpLabel(item) }}</button></div>
                            <span>{{ severityWord(item.issue) + '. ' + item.issue.message }}</span>
                        </li>
                    </ul>
                    <p v-if="liveItems.length > LIVE_SHOWN" class="text-xs">{{ 'And ' + (liveItems.length - LIVE_SHOWN) + ' more.' }}</p>
                    <p v-if="ignoredCount" class="text-xs">
                        {{ ignoredWords(ignoredCount) + ' ' }}
                        <button type="button" class="copydesk-jump" @click="stopIgnoring">Stop ignoring</button>
                    </p>
                </div>

                <!-- One paragraph per line: what to fix reads as a list, not a run-on. -->
                <ui-alert v-if="showRefusal" variant="error">
                    <ui-heading :text="refusal[0]" />
                    <p v-for="(line, i) in refusal.slice(1)" :key="i">{{ line }}</p>
                </ui-alert>

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
        `})})();(function(){Statamic.$components.register("copydesk-scan-progress",{props:["url"],setup(e){const n=Vue.reactive({done:0,total:null});let d=null;async function l(){try{const{data:p}=await Statamic.$app.config.globalProperties.$axios.get(e.url);if(p.total===null){window.location.reload();return}n.done=p.done,n.total=p.total}catch{}d=setTimeout(l,2e3)}return Vue.onMounted(l),Vue.onBeforeUnmount(()=>clearTimeout(d)),{state:n}},template:`
            <div class="space-y-2" role="status" aria-live="polite">
                <p class="text-sm">{{ state.total === null ? 'Starting the scan.' : 'Scanning: ' + state.done + ' of ' + state.total + ' pages done.' }}</p>
                <p class="text-xs">The report shows here when it is done. You can leave this page; the scan carries on.</p>
            </div>
        `})})();(function(){const{injectPublishContext:e}=window.__STATAMIC__.ui,n={error:"To fix",warning:"To look at",tip:"Tip"};Statamic.$components.register("copydesk_rule_tester-fieldtype",{props:["meta"],setup(d){const l=e(),p=Vue.reactive({text:"",trying:!1,findings:null,failed:null});async function g(){var c,i,f,k,b,T;p.trying=!0,p.failed=null;try{const{data:v}=await Statamic.$app.config.globalProperties.$axios.post(d.meta.url,{values:l.values.value,text:p.text});p.findings=v.findings}catch(v){p.findings=null,p.failed=((k=(f=(i=(c=v.response)==null?void 0:c.data)==null?void 0:i.errors)==null?void 0:f.rules)==null?void 0:k[0])||((T=(b=v.response)==null?void 0:b.data)==null?void 0:T.message)||"The rules could not be tried."}finally{p.trying=!1}}return{state:p,run:g,WORDS:n}},template:`
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
