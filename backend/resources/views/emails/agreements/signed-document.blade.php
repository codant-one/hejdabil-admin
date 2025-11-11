<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Ditt signerade dokument</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6;">

    <h2>Hej!</h2>

    @if($agreement)
        <p>Tack för att du har signerat avtalet med nummer <strong>#{{ $agreement->agreement_id }}</strong>.</p>
    @elseif($document)
        <p>Tack för att du har signerat dokumentet <strong>{{ $document->title }}</strong>.</p>
    @else
        <p>Tack för att du har signerat dokumentet.</p>
    @endif
    
    <p>En kopia av det signerade dokumentet finns bifogat i detta e-postmeddelande för din referens.</p>

    @if(isset($downloadUrl) && $downloadUrl)
        <p>Om bilagan inte är tillgänglig, kan du ladda ner dokumentet via följande länk:</p>
        <p><a href="{{ $downloadUrl }}" target="_blank" rel="noopener">Ladda ner signerat dokument</a></p>
    @endif
    
    <p>Med vänliga hälsningar,</p>
    <p><strong>Billogg</strong></p>

</body>
</html>