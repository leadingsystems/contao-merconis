<?php

	/*
	 * Fields
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['title']	= array('Bezeichnung');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['member_group']	= array('Mitgliedergruppe');

    $GLOBALS['TL_LANG']['tl_ls_shop_message_model']['flex_parameters']								= array('Flexible Inhalte', 'Hier können Sie beliebig viele Inhalte hinterlegen, die in Templates über das Schlüsselwort referenziert werden können.');
    $GLOBALS['TL_LANG']['tl_ls_shop_message_model']['flex_parameters_label01']						= 'Parameter-Schlüsselwort';
    $GLOBALS['TL_LANG']['tl_ls_shop_message_model']['flex_parameters_label02']						= 'Parameter-Wert';
	
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['useHTML']	= array('HTML-Inhalt verwenden');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['template_html'] = array('Template für HTML-Mails');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['content_html']	= array('Inhalt für HTML-Nachrichten','Es können spezielle MERCONIS-Platzhalter verwendet werden, um Informationen aus der Bestellung in den Text einzusetzen. Eine Auflistung der verfügbaren Platzhalter finden Sie im MERCONIS-Handbuch.');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['useRawtext']	= array('"Nur-Text"-Inhalt verwenden');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['template_rawtext'] = array('Template für Nur-Text-Mails');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['content_rawtext']	= array('Inhalt für Nur-Text-Nachrichten','Es können spezielle MERCONIS-Platzhalter verwendet werden, um Informationen aus der Bestellung in den Text einzusetzen. Eine Auflistung der verfügbaren Platzhalter finden Sie im MERCONIS-Handbuch.');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['subject'] = array('Betreff');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['senderName'] = array('Absender-Name');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['senderAddress'] = array('Absender-Adresse');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['sendToCustomerAddress1'] = array('Kunden-Adresse', 'Bitte aktivieren Sie diese Checkbox, wenn diese Nachricht an eine der Bestellung hinterlegte E-Mail-Adresse des Kunden gesendet werden soll. Das hierfür angegebene Eingabefeld sollte idealerweise ein Pflichtfeld sein, da die Nachricht ansonsten nicht versandt werden kann.');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['sendToCustomerAddress2'] = array('Alternative Kunden-Adresse', 'Bitte aktivieren Sie diese Checkbox, wenn diese Nachricht an eine andere der Bestellung hinterlegte E-Mail-Adresse des Kunden gesendet werden soll. Sofern der Kunde in dem hierfür angegebenen Eingabefeld keine Adresse angegeben hat, wird standardmäß an die im weiter oben definierten Feld angegebene Adresse versandt.');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['customerDataType'] = array('Art des Kundendaten-Eingabefelds', 'Wählen Sie hier, zu welchem Formular das Eingabefeld gehört.');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['customerDataField'] = array('Name des Kundendaten-Eingabefelds', 'Geben Sie hier den Namen des Eingabefeldes, welches die E-Mail-Adresse enthält, an.');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['sendToSpecificAddress'] = array('Spezielle Adresse', 'Bitte aktivieren Sie diese Checkbox, wenn diese Nachricht an eine spezielle hier fest hinterlegte Adresse gesendet werden soll. Dies ist z. B. für eine Bestellbenachrichtigung Ihre eigene E-Mail-Adresse sinnvoll. Sofern Sie auch den Versand an eine Kunden-Adresse aktiviert haben, wird die hier angegebene Adresse als BCC verwendet.');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['specificAddress'] = array('Spezielle Empfängeradresse');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['sendToMemberAddress'] = array('Mitglieder-Adresse', 'Bitte aktivieren Sie diese Checkbox, wenn die Nachricht an die E-Mail-Adresse des Mitglieds versendet werden soll. Diese Option ist nur im Zusammenhang mit Wiederverfügbarkeitsbenachrichtigungen nutzbar.');

	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['attachments'] = array('Datei-Anhänge');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['dynamicAttachments'] = array('Dynamisch erzeugter PDF-Anhang', 'Wählen Sie hier eine PHP-Datei aus, die zur Erstellung eines dynamisch generierten PDF-Anhangs verwendet wird. Wenn Sie keine Datei auswählen, so wird kein dynamisch generiertes PDF angehängt.');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['published']	= array('Aktiv');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['externalImages'] = array('Externe Bilder', 'Bilder in HTML-Newslettern nicht einbetten.');
	
	/*
	 * Legends
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['title_legend']   = 'Bezeichnung';
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['content_legend']   = 'Inhalt';
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['group_legend']   = 'Zuordnung zu Mitgliedergruppe';
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['subject_legend']   = 'Absender und Betreff';
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['receiver_legend']   = 'Empfänger';
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['attachments_legend']   = 'Anhänge';
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['published_legend']   = 'Aktivierung';
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['expert_legend']     = 'Experten-Einstellungen';
	
	/*
	 * Reference
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['customerDataType']['options'] = array(
		'personalData' => 'Persönliche Daten',
		'paymentData' => 'Kundenangaben zur Zahlung',
		'shippingData' => 'Kundenangaben zum Versand'
	);

	/*
	 * Buttons
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['new']        = array('Neue Nachrichtenvorlage', 'Eine neue Nachrichtenvorlage anlegen');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['edit']        = array('Nachrichtenvorlage bearbeiten', 'Nachrichtenvorlage ID %s bearbeiten');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['delete']        = array('Nachrichtenvorlage löschen', 'Nachrichtenvorlage ID %s löschen');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['copy']        = array('Nachrichtenvorlage kopieren', 'Nachrichtenvorlage ID %s kopieren');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['show']        = array('Details anzeigen', 'Details der Nachrichtenvorlage ID %s anzeigen');
	
	/*
	 * MISC
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['childRecordListText'] = '<p>Für Gruppe <strong>%s</strong></p><p>Betreff: "%s"</p>';
