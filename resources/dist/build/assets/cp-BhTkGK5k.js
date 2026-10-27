(function(){const{injectPublishContext:m}=window.__STATAMIC__.ui,y=[{key:"error",color:"red",one:"1 to fix",many:t=>t+" to fix"},{key:"warning",color:"amber",one:"1 to look at",many:t=>t+" to look at"},{key:"tip",color:"gray",one:"1 tip",many:t=>t+" tips"}],g={on_target:{color:"emerald",words:"on target"},above:{color:"red",words:"harder than your target"},below:{color:"emerald",words:"easier than your target, which is fine"}};function u(t){return String(t).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;")}const o=70;function k(t){if(!t)return"";const n=Array.from(t.text);let e=n.slice(0,t.offset).join("");const f=n.slice(t.offset,t.offset+t.length).join("");let l=n.slice(t.offset+t.length).join("");return n.length>2*o&&t.length<n.length&&(e.length>o&&(e="…"+e.slice(-o).replace(/^\S*\s/,"")),l.length>o&&(l=l.slice(0,o).replace(/\s\S*$/,"")+"…")),u(e)+"<mark>"+u(f)+"</mark>"+u(l)}Statamic.$components.register("readability_panel-fieldtype",{props:["config","meta"],setup(t){const n=m(),e=Vue.reactive({checking:!1,result:null,failed:null,notYet:!1});async function f(){var s,i,r,c,h,p,v;e.checking=!0,e.failed=null,e.notYet=!1;const a=Statamic.$app.config.globalProperties.$axios;try{const{data:d}=await a.post(cp_url("copydesk/check"),{reference:(s=n.reference)==null?void 0:s.value,values:n.values.value,site:(i=n.site)==null?void 0:i.value,collection:(r=t.config)==null?void 0:r.collection,blueprint:(c=t.config)==null?void 0:c.blueprint});e.result=d}catch(d){e.notYet=((h=d.response)==null?void 0:h.status)===422,e.failed=((v=(p=d.response)==null?void 0:p.data)==null?void 0:v.message)||"The check could not be run.",e.result=null}finally{e.checking=!1}}const l=Vue.computed(()=>{var s;const a=(((s=n.errors)==null?void 0:s.value)??{}).copydesk;return Array.isArray(a)?a:[]});Vue.watch(l,a=>{a.length>0&&(e.result=null,e.failed=null)});const b=Vue.computed(()=>l.value.length>0&&!e.result&&!e.failed&&!e.checking),x=Vue.computed(()=>{const a=e.result;if(!a||!a.band)return null;const s=g[a.comparison]??g.on_target;return{color:s.color,text:"Your target is "+a.target.label+". This page reads "+s.words+"."}}),w=Vue.computed(()=>{var s;const a=((s=e.result)==null?void 0:s.findings)??[];return y.map(i=>{const r=a.filter(c=>c.severity===i.key);return{key:i.key,color:i.color,items:r,badge:r.length===1?i.one:i.many(r.length)}}).filter(i=>i.items.length>0)}),_=Vue.computed(()=>{var a;return(((a=e.result)==null?void 0:a.sections)??[]).map(s=>{var i;return{...s,name:s.lead?"Before the first heading":s.title,color:s.band?((i=g[s.comparison])==null?void 0:i.color)??"gray":"gray",label:s.band?s.band.label:"Nothing to grade"}})});function S(a){return a.slice(0,5)}return{state:e,check:f,verdict:x,groups:w,sections:_,highlighted:k,shown:S,refusal:l,showRefusal:b}},template:`
            <div class="space-y-3">
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
