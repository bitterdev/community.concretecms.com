<template>
    <div class="login-form w-50 m-auto">
        <form @submit.prevent.stop="attemptLogin()" method="post">
            <h2 class="text-center">Log in</h2>
            <div class="alert alert-danger" v-if="error">{{ error }}</div>
            <div class="form-group">
                <input name="email" v-model="username" type="email" class="form-control" placeholder="Email" required="required" autocomplete="off">
            </div>
            <div class="form-group">
                <input name="password" v-model="password" type="password" class="form-control" placeholder="Password" required="required" autocomplete="off">
            </div>
            <div class="form-group">
                <button :disabled="authenticating" type="submit" class="btn btn-primary btn-block">Verify Login</button>
            </div>
        </form>
    </div>
</template>

<script>
import {store} from '../../store/store'
import {router} from '../../routes/routes'
import config from '../../config'

export default {
    name: "list",
    data: () => ({
        username: '',
        password: '',
        authenticating: false,
        error: '',
    }),
    methods: {
        async attemptLogin() {
            const self = this
            self.authenticating = true

            let caught = null
            const response = await fetch(`${config.apiBaseUrl}/login`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        email: this.username,
                        password: this.password,
                    })
                })
                .then((response) => response.json())
                .catch((error) => {
                    caught = error
                });

            if (caught) {
                this.error('Unknown error occurred.');
                this.authenticating = false
                return
            }

            // Start validating
            const errorMessage = response.detail || response.message
            if (errorMessage) {
                this.error = errorMessage
                this.authenticating = false
                return
            }

            if (!response.jwt) {
                this.error = 'Invalid login response, please try again later.'
                this.authenticating = false
                return
            }

            store.commit('login', response.jwt)

            self.authenticating = false
            await router.replace('/');
        }
    }
}
</script>

<style scoped>

</style>
