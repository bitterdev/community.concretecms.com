
import { ApolloClient } from 'apollo-client'
import { createHttpLink } from 'apollo-link-http'
import { InMemoryCache } from 'apollo-cache-inmemory'
import { setContext } from 'apollo-link-context'
import { onError } from 'apollo-link-error'
import { store } from '../store/store'
import { router } from '../routes/routes'
import config from '../config'

const httpLink = createHttpLink({
    uri: `${config.apiBaseUrl}/graphql`,
})

const authLink = setContext(async (_, { headers }) => {
    // get the authentication token from local storage if it exists
    const token = store.state.jwt
    // Return the headers to the context so httpLink can read them
    return {
        headers: {
            ...headers,
            authorization: token ? `Bearer ${token}` : ''
        }
    }
})

const errorLink = onError(({graphQLErrors, networkError}) => {
    if (graphQLErrors)
        graphQLErrors.forEach(({ message, locations, path }) =>
            console.log(
                `[GraphQL error]: Message: ${message}, Location: ${locations}, Path: ${path}`,
            ),
        );

    if (networkError) console.log(`[Network error ${networkError.statusCode}]: ${networkError}`);

    if (networkError && networkError.statusCode === 401) {
        if (store.getters.isLoggedIn) {
            store.commit('logout')
        } else {
            router.replace('/api-login')
        }
    }
})

// Create the apollo client
export const apolloClient = new ApolloClient({
    link: errorLink.concat(authLink.concat(httpLink)),
    cache: new InMemoryCache(),
    //connectToDevTools: true
})

