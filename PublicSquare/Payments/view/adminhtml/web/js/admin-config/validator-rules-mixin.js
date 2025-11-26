define(
    [
        'jquery',
        'mage/translate',
    ],
    function requireValidatorRulesMixin($, $t) {
        function requireString({fqn, parent, key, value}) {
            if (typeof value !== 'string') {
                return `${fqn} with value of ${value} should be a string!`;
            }
        }

        function requireStringOrNumber({fqn, parent, key, value}) {
            if (typeof value !== 'string' && typeof value !== 'number') {
                return `${fqn} with value of ${value} should be a string or number!`;
            }
        }

        const cssSchema = {
            backgroundColor: requireString,
            color: requireString,
            fontSize: requireStringOrNumber,
            fontStyle: requireString,
            fontFamily: requireString,
            fontWeight: requireString,
            letterSpacing: requireStringOrNumber,
            lineHeight: requireStringOrNumber,
            padding: requireStringOrNumber,
            textAlign: requireString,
            textDecoration: requireString,
            textShadow: requireString,
            textTransform: requireString,
        };
        [
            '::placeholder',
            '::selection',
            ':hover',
            ':focus',
            ':disabled',
        ].forEach(_ => cssSchema[_] = cssSchema);


        const schema = {
            enableCopy: ({fqn, parent, key, value}) => {
                if (typeof value !== 'boolean') {
                    return {
                        fqn,
                        key,
                        value,
                        message: `Expected value to be boolean!`,
                    };
                }
            },
            placeholder: ({fqn, parent, key, value}) => {
                // TODO: The object form can be used with the default single card form layout.
                // the string form must be used with the split card form layout.
                if (typeof value === 'object') {
                    return validate({
                                        parent, key, value, rules: {
                            cardNumber: requireString,
                            cardExpirationDate: requireString,
                            cardSecurityCode: requireString,
                        },
                                    });
                } else {
                    requireString({fqn, parent, key, value});
                }
            },
            style: {
                base: cssSchema,
                invalid: cssSchema,
                empty: cssSchema,
                complete: cssSchema,
            },
        };


        function traversAndCollect({parent, key, value, rules}) {
            const thisFqn = parent ? `${parent}.${key}` : (
                key ? key : ''
            );
            let violations = [];
            if (typeof value === 'object') {
                for (const [entryKey, entryValue] of Object.entries(value)) {
                    const fqn = `${thisFqn}.${entryKey}`;

                    if (rules.hasOwnProperty(entryKey)) {
                        violations = violations.concat(traversAndCollect(
                            {
                                fqn,
                                parent: thisFqn,
                                key: entryKey,
                                value: entryValue,
                                rules: rules[entryKey],
                            },
                        ));
                    } else if (entryKey) {
                        const allowedKeys = Object.keys(rules);
                        violations.push(
                            {
                                fqn: `${fqn}`,
                                parent: thisFqn,
                                key: entryKey,
                                value: entryValue,
                                allowedKeys,
                                message: `Encountered unexpected key '${entryKey}' in '${thisFqn}'. Allowed keys ${allowedKeys}!`,
                            },
                        );
                    }
                }
            } else if (typeof rules === 'function') {
                violations = violations.concat(rules({thisFqn, parent, key, value}));
            } else {
                console.error('psq: Got unexpected rules value in validation schema at %s! rules: %o', fqn, rules);
                // This is not an error that can be fixed by the end user
            }
            return violations;
        }

        return function (target) {

            $.validator.addMethod(
                'is-json',
                function validateCardInputCustomization(value) {
                    try {
                        JSON.parse(value);
                        return true;
                    } catch (err) {
                        console.warn('psq: Invalid JSON format in %o', value, err);
                        $.mage.__(`Invalid JSON! ${err.message}`);
                    }
                },
            );
            $.validator.addMethod(
                'validate-card-input-customization',
                function validateCardInputCustomization(value, el) {

                    try {
                        const parsed = JSON.parse(value);
                        const violations = traversAndCollect({
                                                                 parent: '',
                                                                 key: '',
                                                                 value: parsed,
                                                                 rules: schema,
                                                             });

                        if (violations.length > 0) {
                            console.warn('Found %d violations! %o', violations.length, violations);
                            const message = violations.map(_ => _.message).join(', \n');
                            // return `Found errors in card input customization JSON! ${message}`;
                            // $.mage.__(`Found errors in card input customization JSON! %1`).replace('%1', message);
                            // $.mage.__(`Found errors in card input customization JSON! %1`).replace('%1', () => message);
                            // return $.mage.__(`Found errors in card input customization JSON!`);
                            // $.mage.__(`Found errors in card input customization JSON!`);
                            // $.mage.__(() => `Found errors in card input customization JSON!`);
                             $.mage.__($t(`Found errors in card input customization JSON!`));
                            /*el.validationMessage = `Found errors in card input customization JSON! ${message}`;
                            el.validity.customError = true;*/
                            // return false;
                        } else {
                            return true;
                        }
                    } catch (err) {
                        console.warn('psq: Invalid JSON format in %o', value, err);
                        $.mage.__(`Invalid JSON! ${err.message}`);
                    }
                },
            );
            return target;
        }
    },
)