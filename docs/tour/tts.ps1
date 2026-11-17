$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$audio = Join-Path $root 'audio'

Add-Type -AssemblyName System.Speech
$synth = New-Object System.Speech.Synthesis.SpeechSynthesizer
$synth.SelectVoice('Microsoft Zira Desktop')
$synth.Rate = 0
$synth.Volume = 100

Get-ChildItem -Path $audio -Filter '*.txt' | Sort-Object Name | ForEach-Object {
    $wav = [IO.Path]::ChangeExtension($_.FullName, '.wav')
    $text = [IO.File]::ReadAllText($_.FullName).Trim()
    $synth.SetOutputToWaveFile($wav)
    $synth.Speak($text)
    Write-Output ("Spoke " + $_.Name + " -> " + (Split-Path -Leaf $wav))
}

$synth.SetOutputToNull()
$synth.Dispose()
Write-Output 'TTS complete.'
