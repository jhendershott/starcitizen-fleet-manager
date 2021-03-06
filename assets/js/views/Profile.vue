<template>
    <div class="animated fadeIn">
        <b-row>
            <b-col col xl="6" v-if="showLinkAccount">
                <b-card header="Link your RSI Account">
                    <b-form>
                        <b-alert variant="success" show>
                            In order to protect your fleet, we need that you <b>link your RSI account</b> to Fleet Manager.<br/>
                            This allows us to prevent impersonation of RSI accounts.<br/>
                            For now, the only best and simplest way is to put a <b>special marker</b> on your <b>RSI biography</b>.
                        </b-alert>
                        <b-button v-b-toggle.collapse-step-1 variant="primary" size="lg" v-if="showButtonStep1">Okay, I'm ready to link my account!</b-button>
                        <b-collapse id="collapse-step-1" class="mt-3" @show="showButtonStep1 = false" v-model="showCollapseStep1">
                            <h4>1. Who are you?</h4>
                            <b-form-group>
                                <p class="">Firstly, let us know your Star Citizen Handle.</p>
                                <b-alert variant="danger" :show="showErrorStep1" v-html="errorStep1Message"></b-alert>
                                <b-input-group>
                                    <b-form-input id="form_handle"
                                                  type="text"
                                                  v-model="form.handle"
                                                  placeholder="Type your SC handle then click on search"
                                                  @keyup.enter="searchHandle"
                                    ></b-form-input>
                                    <b-input-group-append>
                                        <b-btn variant="success" @click="searchHandle" :disabled="searchingHandle">
                                            <template v-if="!searchingHandle"><i class="fas fa-search"></i> Search</template>
                                            <template v-else><i class="fas fa-spinner fa-pulse"></i> Search</template>
                                        </b-btn>
                                    </b-input-group-append>
                                </b-input-group>
                                <div v-if="searchedCitizen != null" class="row mt-3">
                                    <div v-if="searchedCitizen.avatarUrl" class="col col-xs-12 col-md-3 col-lg-2">
                                        <img :src="searchedCitizen.avatarUrl" alt="avatar" class="img-fluid" />
                                    </div>
                                    <div class="col">
                                        <strong>Nickname</strong>: {{ searchedCitizen.nickname }}<br/>
                                        <strong>Handle</strong>: <a :href="'https://robertsspaceindustries.com/citizens/'+searchedCitizen.handle.handle" target="_blank">{{ searchedCitizen.handle.handle }}</a><br/>
                                        <strong>Number</strong>: {{ searchedCitizen.numberSC.number }}<br/>
                                        <strong>Main orga</strong>: <span v-html="searchedCitizen.mainOrga ? formatOrganizationList([searchedCitizen.mainOrga]) : ''"></span><br/>
                                        <strong>All orgas</strong>: <span v-html="formatOrganizationList(searchedCitizen.organizations)"></span><br/>
                                    </div>
                                </div>
                            </b-form-group>
                            <b-row v-if="searchedCitizen == null">
                                <b-col>
                                    <b-alert variant="info" show>
                                        <i class="fas fa-info-circle"></i>
                                        Your <b>SC Handle</b> is your <b>RSI username</b> (not your nickname) and can be visible on your <b>RSI Profile panel</b> or <a href="https://robertsspaceindustries.com/account/settings" target="_blank" style="text-decoration: underline"><b>RSI Settings</b></a>.
                                    </b-alert>
                                </b-col>
                                <b-col lg="5" class="text-right">
                                    <img class="img-fluid" src="../../img/sc-handle.png" alt="How to retrieve your Handle" />
                                </b-col>
                            </b-row>
                        </b-collapse>

                        <b-button v-b-toggle.collapse-step-2 variant="primary" size="lg" v-if="showButtonStep2">Great, this is my account, let's continue!</b-button>
                        <b-collapse id="collapse-step-2" class="mt-3" @show="showButtonStep2 = false" v-model="showCollapseStep2">
                            <h4>2. Special marker</h4>
                            <p>
                                Finally, you have to put this following token into your <a href="https://robertsspaceindustries.com/account/profile" target="_blank" style="text-decoration: underline"><b>RSI short bio</b></a>.<br/>
                                Don't worry, you can remove it just after your successful link. ;)
                            </p>
                            <b-alert variant="danger" :show="showError" v-html="errorMessage"></b-alert>
                            <div v-if="lastShortBio != null">
                                <strong>Your current bio:</strong>
                                <p style="max-height: 150px; overflow-y: auto;">{{ lastShortBio }}</p>
                            </div>
                            <b-form-group>
                                <b-input-group prepend="Token">
                                    <b-form-input readonly
                                                  id="form_user_token"
                                                  type="text"
                                                  v-model="userToken"></b-form-input>
                                    <b-input-group-append>
                                        <b-btn :variant="copied ? 'success' : 'outline-success'"
                                               v-clipboard:copy="userToken"
                                               v-clipboard:success="onCopyToken">{{ copied ? 'Copied' : 'Copy' }}</b-btn>
                                    </b-input-group-append>
                                </b-input-group>
                            </b-form-group>
                            <b-button type="button" :disabled="submitDisabled" size="lg" variant="success" @click="linkAccount">Done! I've pasted the token in my bio.</b-button>
                        </b-collapse>
                    </b-form>
                </b-card>
            </b-col>
            <b-col col md="6" v-if="showUpdateHandle">
                <UpdateScHandle :citizen="citizen"></UpdateScHandle>
            </b-col>
        </b-row>
        <b-row>
            <b-col col md="6" v-if="showUpdateHandle">
                <b-card header="Preferences" class="js-preferences">
                    <b-form>
                        <b-button type="button" variant="secondary" :disabled="refreshingProfile" @click="refreshMyRsiProfile" class="mb-3" title="Force to retrieve your public profile from RSI"><i class="fas fa-sync-alt" :class="{'fa-spin': refreshingProfile}"></i>
                            Refresh my RSI Profile</b-button>

                        <b-form-group label="Personal fleet policy" class="col-4">
                            <b-form-radio v-model="publicChoice" @change="savePublicChoice" :disabled="savingPreferences" name="public-choice" value="private">Private <i class="fas fa-info-circle" v-b-tooltip.hover title="Only you see your fleet and your name is hidden on your orgas' fleets."></i></b-form-radio>
                            <b-form-radio v-model="publicChoice" @change="savePublicChoice" :disabled="savingPreferences" name="public-choice" value="orga">Organizations only <i class="fas fa-info-circle" v-b-tooltip.hover title="Allows you to fine-grained set your visibility on each orgas."></i></b-form-radio>
                            <b-form-radio v-model="publicChoice" @change="savePublicChoice" :disabled="savingPreferences" name="public-choice" value="public">Public <i class="fas fa-info-circle" v-b-tooltip.hover title="Everyone can see your fleet and your name is visible in your orgas' fleets."></i></b-form-radio>
                        </b-form-group>
                        <div v-if="publicChoice === 'orga'" class="mb-3">
                            <div v-for="orga in citizen.organizations" :key="orga.organization.organizationSid">
                                <div class="d-inline-block" style="min-width:150px;">{{ orga.organization.name }}</div>
                                <div class="d-inline-block">
                                    <b-form-radio class="d-inline-block mr-3" v-model="orgaVisibilityChoices[orga.organization.organizationSid]" @change="saveOrgaVisibilityChoices($event, orga.organization)" :disabled="savingPreferences" :name="'orga-visibility-choice-'+orga.organization.organizationSid" value="private">Private <i class="fas fa-info-circle" v-b-tooltip.hover title="Your fleet is not visible for these orga's members and your name is not visible on this orga's fleet."></i></b-form-radio>
                                    <b-form-radio class="d-inline-block mr-3" v-model="orgaVisibilityChoices[orga.organization.organizationSid]" @change="saveOrgaVisibilityChoices($event, orga.organization)" :disabled="savingPreferences" :name="'orga-visibility-choice-'+orga.organization.organizationSid" value="admin">Admin only <i class="fas fa-info-circle" v-b-tooltip.hover title="Only the highest ranks (admins) of this orga can see your fleet and your name in this orga's fleet."></i></b-form-radio>
                                    <b-form-radio class="d-inline-block" v-model="orgaVisibilityChoices[orga.organization.organizationSid]" @change="saveOrgaVisibilityChoices($event, orga.organization)" :disabled="savingPreferences" :name="'orga-visibility-choice-'+orga.organization.organizationSid" value="orga">Orga only <i class="fas fa-info-circle" v-b-tooltip.hover title="You and these orga's members only can see your fleet and your name is visible on this orga's fleet."></i></b-form-radio>
                                </div>
                            </div>
                        </div>
                        <b-form-group>
                            <b-input-group prepend="My fleet link">
                                <b-form-input readonly
                                              id="my_fleet_link"
                                              type="text"
                                              v-model="myFleetLink"></b-form-input>
                                <b-input-group-append>
                                    <b-btn :variant="fleetLinkCopied ? 'success' : 'outline-success'"
                                           v-clipboard:copy="myFleetLink"
                                           v-clipboard:success="onCopyFleetLink">{{ fleetLinkCopied ? 'Copied' : 'Copy' }}</b-btn>
                                </b-input-group-append>
                            </b-input-group>
                        </b-form-group>
                        <b-alert variant="warning" :show="citizen.countRedactedOrganizations > 0">
                            <h5><i class="fas fa-exclamation-triangle"></i> Redacted orgas</h5>
                            You have <strong>{{ citizen.countRedactedOrganizations }} redacted organizations</strong><template v-if="citizen.redactedMainOrga"> including your main orga</template>. Therefore, you will not be able to see their fleet.<br/>
                            To display them, you have to set <strong>"Visible"</strong> in your <a href="https://robertsspaceindustries.com/account/organization" target="_blank">RSI account</a>.
                        </b-alert>
                        <strong>Supporters preferences</strong>
                        <b-form-checkbox v-model="supporterVisible" @change="saveSupporterVisible" :disabled="savingPreferences" name="supporter-visibility" switch>
                            Display my name on Supporters page
                        </b-form-checkbox>
                    </b-form>
                </b-card>
            </b-col>
        </b-row>
        <b-row>
            <b-col col md="6" v-if="user != null">
                <Security :user="user" @accountLinked="onAccountLinked"></Security>
            </b-col>
        </b-row>
    </div>
</template>

<script>
    import axios from 'axios';
    import UpdateScHandle from "./UpdateSCHandle";
    import Security from "./Security";
    import { mapMutations } from 'vuex';
    import VueClipboard from 'vue-clipboard2';

    import Vue from "vue";
    Vue.use(VueClipboard);

    export default {
        name: 'profile',
        components: {Security, UpdateScHandle},
        data() {
            return {
                form: {
                    handle: null,
                },
                user: null,
                citizen: null,
                myFleetLink: null,
                publicChoice: null,
                orgaVisibilityChoices: {},
                manageableOrgas: [],
                savingPreferences: false,
                preferencesLoaded: false,
                userToken: null,
                copied: false,
                fleetLinkCopied: false,
                submitDisabled: false,
                showError: false,
                errorMessage: null,
                showLinkAccount: false,
                showUpdateHandle: false,
                refreshingProfile: false,
                showButtonStep1: true,
                showButtonStep2: false,
                showErrorStep1: false,
                errorStep1Message: '',
                searchedCitizen: null,
                searchingHandle: false,
                lastShortBio: null,
                showCollapseStep1: false,
                showCollapseStep2: false,
                supporterVisible: null,
            }
        },
        created() {
            this.refreshProfile();
        },
        methods: {
            ...mapMutations(['updateProfile']),
            savePublicChoice(value) {
                this.publicChoice = value;
                this.savePreferences();
            },
            saveOrgaVisibilityChoices(value, orga) {
                this.$set(this.orgaVisibilityChoices, orga.organizationSid, value);
                this.savePreferences();
            },
            saveSupporterVisible(value) {
                this.supporterVisible = value;
                this.savePreferences();
            },
            savePreferences() {
                this.savingPreferences = true;
                axios.post('/api/profile/save-preferences', {
                    publicChoice: this.publicChoice,
                    orgaVisibilityChoices: this.orgaVisibilityChoices,
                    supporterVisible: this.supporterVisible,
                }).then(response => {
                    this.$toastr.s('Changes saved');
                }).catch(err => {
                    this.checkAuth(err.response);
                    this.$toastr.e('An error has occurred. Please try again later.');
                }).then(_ => {
                    this.savingPreferences = false;
                });
            },
            onCopyToken() {
                this.copied = true;
            },
            onCopyFleetLink() {
                this.fleetLinkCopied = true;
            },
            onAccountLinked() {
                this.refreshProfile();
            },
            refreshProfile() {
                axios.get('/api/profile').then(response => {
                    this.user = response.data;
                    this.citizen = this.user.citizen;
                    this.showLinkAccount = !this.citizen;
                    this.showUpdateHandle = !!this.citizen;
                    this.userToken = this.user.token;
                    this.myFleetLink = this.getMyFleetLink();
                    this.publicChoice = this.user.publicChoice;
                    this.supporterVisible = this.user.supporterVisible;
                    this.updateProfile(this.citizen);

                    if (this.citizen) {
                        for (let orga of this.citizen.organizations) {
                            this.$set(this.orgaVisibilityChoices, orga.organization.organizationSid, orga.visibility);
                        }
                    }
                }).catch(err => {
                    this.checkAuth(err.response);
                    this.showError = true;
                    if (err.response.data.errorMessage) {
                        this.errorMessage = err.response.data.errorMessage;
                    }
                });
            },
            getMyFleetLink() {
                if (!this.citizen) {
                    return '';
                }

                return `${window.location.protocol}//${window.location.host}/citizen/${this.citizen.actualHandle.handle}`;
            },
            refreshMyRsiProfile(ev) {
                this.refreshingProfile = true;
                axios.post('/api/profile/refresh-rsi-profile').then(response => {
                    this.$toastr.s('Your RSI public profile has been successfully refreshed.');
                    this.refreshProfile();
                }).catch(err => {
                    this.checkAuth(err.response);
                    if (err.response.data.errorMessage) {
                        this.$toastr.e(err.response.data.errorMessage);
                    }
                }).then(_ => {
                    this.refreshingProfile = false;
                });
            },
            searchHandle() {
                if (!this.form.handle) {
                    return;
                }

                this.searchedCitizen = null;
                this.showErrorStep1 = false;
                this.showCollapseStep2 = false;
                this.searchingHandle = true;
                axios.get('/api/profile/search-handle', {
                    params: {handle: this.form.handle}
                }).then(response => {
                    this.searchedCitizen = response.data;
                    this.showButtonStep2 = true;
                }).catch(err => {
                    if (err.response.data.error === 'invalid_form') {
                        this.errorStep1Message = err.response.data.formErrors.join('<br/>');
                    } else if (err.response.data.error === 'not_found_handle') {
                        this.errorStep1Message = `Sorry, it seems that <a href="https://robertsspaceindustries.com/citizens/${this.form.handle}" target="_blank">SC Handle ${this.form.handle}</a> does not exist. Try to check the typo and search again.`;
                    } else {
                        this.errorStep1Message = `Sorry, an unexpected error has occurred. Please try again later.`;
                    }
                    this.showErrorStep1 = true;
                    this.showButtonStep2 = false;
                }).then(_ => {
                    this.searchingHandle = false;
                });
            },
            formatOrganizationList(orgas) {
                let res = [];
                for (let orga of orgas) {
                    res.push(`<a href="https://robertsspaceindustries.com/orgs/${orga.sid.sid}" target="_blank">${orga.sid.sid}</a>`);
                }
                return res.join(', ');
            },
            linkAccount() {
                const form = new FormData();
                form.append('handleSC', this.searchedCitizen.handle.handle);

                this.lastShortBio = null;
                this.showError = false;
                this.errorMessage = 'An error has occurred. Please try again in a moment.';
                this.submitDisabled = true;
                axios.post('/api/profile/link-account', form).then(response => {
                    this.refreshProfile();
                    this.$toastr.s('Your RSI account has been successfully linked! You can remove the token from your bio.');
                    this.submitDisabled = false;
                }).catch(async err => {
                    this.submitDisabled = false;
                    if (err.response.data.error === 'invalid_form') {
                        const response = await axios.get('/api/profile/search-handle', {
                            params: {handle: this.form.handle}
                        });
                        if (response.data) {
                            this.searchedCitizen = response.data;
                            this.lastShortBio = this.searchedCitizen.bio;
                        }
                    }
                    if (err.response.data.errorMessage) {
                        this.errorMessage = err.response.data.errorMessage;
                    } else if (err.response.data.error === 'invalid_form') {
                        this.errorMessage = err.response.data.formErrors.join("\n");
                    }
                    this.showError = true;
                });
            },
            checkAuth(response) {
                const status = response.status;
                const data = response.data;
                if ((status === 401 && data.error === 'no_auth')
                    || (status === 403 && data.error === 'forbidden')) {
                    window.location = data.loginUrl;
                }
            }
        }
    }
</script>
