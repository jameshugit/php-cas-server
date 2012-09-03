<?php
/**
 * SAML 2.0 remote IdP metadata for simpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 */

$metadata['urn:fi:ac-lyon:AA:1.0'] = array (
  'entityid' => 'urn:fi:ac-lyon:AA:1.0',
  'name' => 
  array (
    'en-us' => 'Académie de Lyon',
  ),
  'description' => 
  array (
    'en-us' => 'Academie Lyon',
  ),
  'OrganizationName' => 
  array (
    'en-us' => 'Academie Lyon',
  ),
  'OrganizationDisplayName' => 
  array (
    'en-us' => 'Académie de Lyon',
  ),
  'url' => 
  array (
    'en-us' => 'http://www.ac-lyon.fr/',
  ),
  'OrganizationURL' => 
  array (
    'en-us' => 'http://www.ac-lyon.fr/',
  ),
  'contacts' => 
  array (
    0 => 
    array (
      'contactType' => 'technical',
      'company' => 'Académie de  Lyon',
      'givenName' => 'Dominique',
      'surName' => 'Cretin',
      'emailAddress' => 
      array (
        0 => 'rssi@ac-lyon.fr',
      ),
      'telephoneNumber' => 
      array (
        0 => '04.72.80.60.29',
      ),
    ),
  ),
  'metadata-set' => 'saml20-idp-remote',
  'SingleSignOnService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://portail.ac-lyon.fr/sso/SSO',
    ),
    1 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
      'Location' => 'https://portail.ac-lyon.fr/sso/SSO',
    ),
    2 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
      'Location' => 'https://portail.ac-lyon.fr/sso/SSO',
    ),
    3 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
      'Location' => 'https://portail.ac-lyon.fr/soap/services/SAMLMessageProcessor/AP',
    ),
  ),
  'SingleLogoutService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://portail.ac-lyon.fr/slo/request/AP',
      'ResponseLocation' => 'https://portail.ac-lyon.fr/slo/response/AP',
    ),
    1 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
      'Location' => 'https://portail.ac-lyon.fr/slo/request/AP',
      'ResponseLocation' => 'https://portail.ac-lyon.fr/slo/response/AP',
    ),
    2 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
      'Location' => 'https://portail.ac-lyon.fr/slo/request/AP',
      'ResponseLocation' => 'https://portail.ac-lyon.fr/slo/response/AP',
    ),
    3 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
      'Location' => 'https://portail.ac-lyon.fr/soap/services/SAMLMessageProcessor/AP',
    ),
  ),
  'ArtifactResolutionService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
      'Location' => 'https://portail.ac-lyon.fr/soap/services/SAMLMessageProcessor/AP',
      'index' => 0,
      'isDefault' => true,
    ),
  ),
  'keys' => 
  array (
    0 => 
    array (
      'encryption' => true,
      'signing' => false,
      'type' => 'X509Certificate',
      'X509Certificate' => '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',
    ),
    1 => 
    array (
      'encryption' => false,
      'signing' => true,
      'type' => 'X509Certificate',
      'X509Certificate' => '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',
    ),
  ),
);


$metadata['urn:fi:ac-lyon:ts:1.0'] = array (
  'entityid' => 'urn:fi:ac-lyon:ts:1.0',
  'name' => 
  array (
    'en-us' => 'Académie de Lyon profile Eleve/Parent',
  ),
  'description' => 
  array (
    'en-us' => 'Académie Lyon',
  ),
  'OrganizationName' => 
  array (
    'en-us' => 'Académie Lyon',
  ),
  'OrganizationDisplayName' => 
  array (
    'en-us' => 'Académie Lyon',
  ),
  'url' => 
  array (
    'en-us' => 'http://www.ac-lyon.fr',
  ),
  'OrganizationURL' => 
  array (
    'en-us' => 'http://www.ac-lyon.fr',
  ),
  'contacts' => 
  array (
    0 => 
    array (
      'contactType' => 'technical',
      'company' => 'Académie Lyon',
      'givenName' => 'Dominique',
      'surName' => 'CRETIN',
      'emailAddress' => 
      array (
        0 => 'rssi@ac-lyon.fr',
      ),
      'telephoneNumber' => 
      array (
        0 => '04 72 80 60 29',
      ),
    ),
  ),
  'metadata-set' => 'saml20-idp-remote',
  'SingleSignOnService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://services.ac-lyon.fr/sso/SSO',
    ),
    1 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
      'Location' => 'https://services.ac-lyon.fr/sso/SSO',
    ),
    2 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
      'Location' => 'https://services.ac-lyon.fr/sso/SSO',
    ),
    3 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
      'Location' => 'https://services.ac-lyon.fr/soap/services/SAMLMessageProcessor/AP',
    ),
  ),
  'SingleLogoutService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://services.ac-lyon.fr/slo/request/AP',
      'ResponseLocation' => 'https://services.ac-lyon.fr/slo/response/AP',
    ),
    1 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
      'Location' => 'https://services.ac-lyon.fr/slo/request/AP',
      'ResponseLocation' => 'https://services.ac-lyon.fr/slo/response/AP',
    ),
    2 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
      'Location' => 'https://services.ac-lyon.fr/ts',
      'ResponseLocation' => 'https://services.ac-lyon.fr/slo/response/AP',
    ),
    3 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
      'Location' => 'https://services.ac-lyon.fr/soap/services/SAMLMessageProcessor/AP',
    ),
  ),
  'ArtifactResolutionService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
      'Location' => 'https://services.ac-lyon.fr/soap/services/SAMLMessageProcessor/AP',
      'index' => 0,
      'isDefault' => true,
    ),
  ),
  'keys' => 
  array (
    0 => 
    array (
      'encryption' => true,
      'signing' => false,
      'type' => 'X509Certificate',
      'X509Certificate' => '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',
    ),
    1 => 
    array (
      'encryption' => false,
      'signing' => true,
      'type' => 'X509Certificate',
      'X509Certificate' => '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',
    ),
  ),
);

