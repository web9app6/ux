import { BackendAction, ChildrenFingerprints } from './Backend';

export default class {
    private url: string;
    private readonly csrfToken: string | null;

    constructor(url: string, csrfToken: string | null = null) {
        this.url = url;
        this.csrfToken = csrfToken;
    }

    buildRequest(
        props: any,
        actions: BackendAction[],
        updated: {[key: string]: any},
        children: ChildrenFingerprints,
        updatedPropsFromParent: {[key: string]: any},
    ): { url: string; fetchOptions: RequestInit } {
        const splitUrl = this.url.split('?');
        let [url] = splitUrl;
        const [, queryString] = splitUrl;
        const params = new URLSearchParams(queryString || '');

        const fetchOptions: RequestInit = {};
        fetchOptions.headers = {
            Accept: 'application/vnd.live-component+html',
        };

        const hasFingerprints = Object.keys(children).length > 0;
        if (
            actions.length === 0 &&
            this.willDataFitInUrl(JSON.stringify(props), JSON.stringify(updated), params, JSON.stringify(children), JSON.stringify(updatedPropsFromParent))
        ) {
            params.set('props', JSON.stringify(props));
            params.set('updated', JSON.stringify(updated));
            if (Object.keys(updatedPropsFromParent).length > 0) {
                params.set('propsFromParent', JSON.stringify(updatedPropsFromParent));
            }
            if (hasFingerprints) {
                params.set('children', JSON.stringify(children));
            }
            fetchOptions.method = 'GET';
        } else {
            fetchOptions.method = 'POST';
            fetchOptions.headers['Content-Type'] = 'application/json';
            const requestData: any = { props, updated };
            if (Object.keys(updatedPropsFromParent).length > 0) {
                requestData.propsFromParent = updatedPropsFromParent;
            }
            if (hasFingerprints) {
                requestData.children = children;
            }

            if (actions.length > 0) {
                // one or more ACTIONs
                if (this.csrfToken) {
                    fetchOptions.headers['X-CSRF-TOKEN'] = this.csrfToken;
                }

                if (actions.length === 1) {
                    // simple, single action
                    requestData.args = actions[0].args;

                    url += `/${encodeURIComponent(actions[0].name)}`;
                } else {
                    url += '/_batch';
                    requestData.actions = actions;
                }
            }

            fetchOptions.body = JSON.stringify(requestData);
        }

        const paramsString = params.toString();

        return {
            url: `${url}${paramsString.length > 0 ? `?${paramsString}` : ''}`,
            fetchOptions,
        }
    }

    private willDataFitInUrl(propsJson: string, updatedJson: string, params: URLSearchParams, childrenJson: string, propsFromParentJson: string) {
        const urlEncodedJsonData = new URLSearchParams(propsJson + updatedJson + childrenJson + propsFromParentJson).toString();

        // if the URL gets remotely close to 2000 chars, it may not fit
        return (urlEncodedJsonData + params.toString()).length < 1500;
    }
}
