<template>
    <div>
        <v-container>
            <v-form v-model="valid">
                Select range:
                <v-row>
                    <v-col>
                        <v-text-field
                            v-model="min"
                            type="number"
                            label="Min"
                            :rules="[rules.required, rules.notMatchMin, rules.greaterOrEqualZero]"
                            :disabled="process"
                        />
                    </v-col>
                    <v-col>
                        <v-text-field
                            v-model="max"
                            type="number"
                            label="Max"
                            :rules="[rules.required, rules.notMatchMax, rules.greaterOrEqualZero]"
                            :disabled="process"
                        />
                    </v-col>
                </v-row>

                <div class="text-caption">
                    We will ask to connect your metamask. Use ROPSTEN network, please.
                </div>

                <div v-if="mainError" class="text-center" style="margin:10px 0 0 0;">
                    <span class="error--text">{{ mainError }}</span>
                </div>

                <div v-if="process" class="text-center" style="margin:10px 0 0 0;">
                    <span class="primary--text">{{ processInfo }}</span>
                </div>

                <div v-if="relayEntry" class="text-center" style="margin:0 0 10px 0;">
                    <a :class="['text-caption', {'error--text': mainError}]"
                       :href="`https://ropsten.etherscan.io/tx/${relayEntry.hash}`"
                       target="_blank"
                    >
                        <span style="vertical-align:middle">{{ shortHash(relayEntry.hash) }}</span>
                        <v-icon style="margin-left:5px" small :color="mainError ? 'error' : 'primary'"
                        >mdi-open-in-new
                        </v-icon>
                    </a>
                </div>

                <div class="text-center" style="margin-top:10px">
                    <v-btn style="color:#46dcb5" color="secondary" :disabled="!valid" :loading="process"
                           @click="start">
                        Generate!
                    </v-btn>
                </div>
            </v-form>

            <div v-if="result">
                <div class="text-center text-h4" style="margin-top:30px">Done!</div>
                <table class="resultTable">
                    <tr>
                        <td>Transaction:</td>
                        <td>
                            <a :href="`https://ropsten.etherscan.io/tx/${result.hash}`" target="_blank">
                                <span style="vertical-align:middle">{{ shortHash(result.hash) }}</span>
                                <v-icon style="margin-left:5px" color="primary" small>mdi-open-in-new</v-icon>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align:right">Random Beacon seed:</td>
                        <td>{{ result.seed }}</td>
                    </tr>
                </table>
                <div class="text-center text-h5">Your random number:</div>
                <div class="text-center text-h3" style="margin-top:5px">
                    <v-icon color="primary" large>mdi-arrow-right</v-icon>
                    <span class="primary--text" style="vertical-align:middle">{{ result.number }}</span>
                    <v-icon color="primary" large>mdi-arrow-left</v-icon>
                </div>
            </div>
        </v-container>
    </div>
</template>

<script>
import ethers from 'ethers';
import seedRandom from 'seedrandom';
import RandomBeaconImpl from '../../KeepRandomBeaconServiceImplV1.json';
import RandomBeaconService from '../../KeepRandomBeaconService.json';

export default {
    components: {},
    data() {
        return {
            min: '0',
            max: '1000',
            rules: {
                required: value => !!value || 'Required.',
                greaterOrEqualZero: value => value - 0 > -1 || 'Must be greater or equal 0.',
                notMatchMin: value => value - 0 !== this.max - 0 || 'Numbers must not match.',
                notMatchMax: value => value - 0 !== this.min - 0 || 'Numbers must not match.',
            },
            valid: true,
            process: false,
            processInfo: false,
            mainError: null,
            relayEntry: null,
            result: null,
            network: null,
        }
    },
    // mounted() {
    //     this.generate('3498723498850193908178237841231', '0x8f2ff22900d48f10381c6b1e78277cd2f866820763da30366a929bab8a0237c1');
    // },
    methods: {
        start() {
            this.result = null;
            this.relayEntry = null;
            this.process = true;
            this.processInfo = 'Metamask connecting...';
            this.mainError = null;
            if (undefined === window.ethereum) {
                this.processError('Please, install the Metamask extension for using this DApp.');

                return;
            }

            window.ethereum.send('eth_requestAccounts').then(() => {
                const provider = new ethers.providers.Web3Provider(window.ethereum);
                provider.getNetwork().then((network) => {
                    this.network = network;
                    if (network.chainId !== 3) {
                        this.processError('Use ROPSTEN network, please.');
                        return;
                    }
                    this.requestSeed(provider).then(({tx, seed}) => {
                        this.generate(seed, tx);
                    }).catch(err => {
                        this.processError('Process error. Try later.', err);
                    });
                }).catch(err => {
                    this.processError('Cannot determine network.', err);
                });
            }).catch(err => {
                this.processError('Metamask connection error.', err);
            });
        },
        requestSeed(provider) {
            const signer = provider.getSigner();
            const address = RandomBeaconService.networks['3'].address;
            const serviceContract = new ethers.Contract(address, RandomBeaconImpl.abi, signer);

            const ret = new Promise((res) => {
                serviceContract.on('*', function (ev) {
                    if (ev.event === 'RelayEntryGenerated') {
                        console.log('RelayEntryGenerated');
                        console.log(`[https://ropsten.etherscan.io/tx/${ev.transactionHash}] generated ${ev.args[0]}`);
                        serviceContract.removeAllListeners();
                        res({txHash: ev.transactionHash, num: ev.args[1]});
                    }
                }.bind(this));
            });
            // const estimatedGas = ethers.utils.parseEther(manualEstimatedGas);

            serviceContract.entryFeeEstimate(0).then((estimatedGas) => {
                console.log(`Estimated gas ${estimatedGas}`);
                this.requestRelay(serviceContract, estimatedGas);
            }).catch(err => {
                this.processError('Could not estimate gas.', err);
                // console.log(`Could not estimate gas: ${err}`)
                // const estimatedGas = ethers.utils.parseEther(manualEstimatedGas);
                // console.log(`Using manual ${estimatedGas}`)
                // this.requestRelay(serviceContract, estimatedGas);
            });

            return ret;
        },
        requestRelay(serviceContract, estimatedGas) {
            this.processInfo = 'Relay requesting...';
            serviceContract['requestRelayEntry()']({value: estimatedGas, gasLimit: 300000}).then((relayEntry) => {
                console.log('Entry requested')
                this.relayEntry = relayEntry;
                this.processInfo = 'Relay submitting...';
                relayEntry.wait().then((n) => {
                    console.log('Entry submitted:');
                    console.log(n);
                    this.processInfo = 'Waiting of generating relay entry...';
                }).catch(err => {
                    this.processError('Fail transaction: ', err);
                });
            }).catch(err => {
                this.processError('Could not request relay entry.', err);
            });
        },
        generate(seed, hash) {
            const generator = seedRandom(seed);
            const number = Math.ceil(generator() * (this.max - this.min) + this.min);
            this.result = {
                'hash': hash,
                'seed': seed,
                'number': number,
            };
            console.log(this.result);
        },
        shortHash(hash) {
            return hash.substring(0, 7) + '...' + hash.substring(hash.length - 5, hash.length);
        },
        processError(text, err = null) {
            this.mainError = text;
            this.process = false;
            console.log(`${text}: ${err}`);
        }
    }
}
</script>

<style lang="scss">
.resultTable {
    width: 100%;
    margin: 10px 0;
}

.resultTable td {
    width: 50%;
    padding: 0 2px;
}

.resultTable td:first-child {
    text-align: right
}
</style>
