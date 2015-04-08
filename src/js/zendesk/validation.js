Validation.addAllThese([
    ['validate-zendesk-sso-token', 'Token cannot be empty', function (v) {
        if ($('zendesk_sso_enabled').getValue() === '1') {
            return !Validation.get('IsEmpty').test(v);
        } else {
            return true;
        }
    }],
    ['validate-zendesk-sso-frontend-token', 'Token cannot be empty', function (v) {
        if ($('zendesk_sso_frontend_enabled').getValue() === '1') {
            return !Validation.get('IsEmpty').test(v);
        } else {
            return true;
        }
    }]
]);
