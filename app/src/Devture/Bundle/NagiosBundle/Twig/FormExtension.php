<?php

namespace Devture\Bundle\NagiosBundle\Twig;

use Devture\Component\Form\Binder\BinderInterface;
use Devture\Component\Form\Token\TokenManagerInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Reimplements the form-layer Twig helpers the ported management templates
 * rely on, on the modern Twig API:
 *
 *  - render_form_violations(form, fieldKey) — renders the validation errors
 *    recorded for a field during the last bind();
 *  - render_form_csrf_token(form) — emits the hidden CSRF field for a form.
 *
 * (The bare csrf_token(intention) helper the templates also use is provided
 * natively by symfony/twig-bridge once security-csrf is installed.)
 *
 * No translator is wired yet (the app is single-locale); violation messages
 * pass through unchanged after parameter substitution, matching the legacy
 * behaviour when no translator was configured.
 */
class FormExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_form_violations', $this->renderFormViolations(...), [
                'is_safe' => ['html'],
                'needs_environment' => true,
            ]),
            new TwigFunction('render_form_csrf_token', $this->renderFormCsrfToken(...), [
                'is_safe' => ['html'],
            ]),
        ];
    }

    public function renderFormViolations(Environment $twig, BinderInterface $form, string $fieldKey): string
    {
        $errors = $form->getViolations()->get($fieldKey);
        if (count($errors) === 0) {
            return '';
        }

        $messages = array_map(static function (array $error): string {
            return str_replace(array_keys($error['params']), array_values($error['params']), $error['message']);
        }, $errors);

        return $twig->render('form/violation_errors.html.twig', ['fieldKey' => $fieldKey, 'messages' => $messages]);
    }

    public function renderFormCsrfToken(BinderInterface $form): string
    {
        $tokenManager = $form->getCsrfTokenManager();
        if (!$tokenManager instanceof TokenManagerInterface) {
            return '';
        }

        $token = htmlspecialchars((string) $tokenManager->generate($form->getCsrfIntention()), ENT_QUOTES);

        return '<input type="hidden" name="' . $form->getCsrfTokenFieldName() . '" value="' . $token . '" />';
    }
}
