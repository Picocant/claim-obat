<html>

<head>
    <link href="file2.css" rel="stylesheet" type="text/css" />
    <table width='100%' border='0' align='center' cellpadding='3px' cellspacing='0' class='tbl_form'>
        <tr class='isi2'>
            <td valign='top' align='center'>
                <font size='4' face='Tahoma'>RS SIMRS KHANZA</font><br>
                GUWOSARI, Pajangan, Bantul<br>
                Hp: 08562675039, 085296559963, E-mail : khanzasoftmedia@gmail.com<br><br>
                <font size='2' face='Tahoma'>ASESMEN AWAL MEDIS HEMODIALISA<br><br></font>
            </td>
        </tr>
    </table>
</head>

<body>
    <table width="100%" border="0" align="center" cellpadding="1px" cellspacing="0" class="tbl_form">

        <!-- IDENTITAS -->
        <tr class="isi">
            <td>No.Rawat :</td>
            <td><input type="text" name="norawat"></td>
            <td>Dokter :</td>
            <td><input type="text" name="dokter"></td>
            <td>Tgl.Lahir :</td>
            <td><input type="date" name="tgllahir"></td>
            <td>J.K. :</td>
            <td><select>
                    <option>L</option>
                    <option>P</option>
                </select></td>
        </tr>
        <tr class="isi">
            <td>Tanggal :</td>
            <td><input type="datetime-local" name="tanggal"></td>
            <td>Anamnesis :</td>
            <td><select>
                    <option>Autoanamnesis</option>
                    <option>Aloanamnesis</option>
                </select></td>
            <td>Asal Poli/Ruangan :</td>
            <td><input type="text"></td>
            <td>Riwayat Alergi Obat :</td>
            <td><input type="text"></td>
        </tr>

        <!-- RIWAYAT PENYAKIT -->
        <tr class="isi">
            <td colspan="8"><b>I. RIWAYAT PENYAKIT</b></td>
        </tr>
        <tr class="isi">
            <td>Mengalami Hipertensi :</td>
            <td><select>
                    <option>Tidak</option>
                    <option>Ya</option>
                </select></td>
            <td>Bengkak Seluruh Tubuh :</td>
            <td><select>
                    <option>Tidak</option>
                    <option>Ya</option>
                </select></td>
            <td>Diabetes Melitus :</td>
            <td><select>
                    <option>Tidak</option>
                    <option>Ya</option>
                </select></td>
            <td>Urin Berdarah :</td>
            <td><select>
                    <option>Tidak</option>
                    <option>Ya</option>
                </select></td>
        </tr>
        <tr class="isi">
            <td>Batu Saluran Kemih :</td>
            <td><select>
                    <option>Tidak</option>
                    <option>Ya</option>
                </select></td>
            <td>Penyakit Ginjal Laom :</td>
            <td><select>
                    <option>Tidak</option>
                    <option>Ya</option>
                </select></td>
            <td>Operasi Saluran Kemih :</td>
            <td><select>
                    <option>Tidak</option>
                    <option>Ya</option>
                </select></td>
            <td>Penyakit Lain :</td>
            <td><input type="text"></td>
        </tr>
        <tr class="isi">
            <td>Infeksi Saluran Kemih :</td>
            <td><select>
                    <option>Tidak</option>
                    <option>Ya</option>
                </select></td>
            <td>Konsumsi Obat Nefrotoksis :</td>
            <td><select>
                    <option>Tidak</option>
                    <option>Ya</option>
                </select></td>
        </tr>

        <!-- RIWAYAT DIALISIS -->
        <tr class="isi">
            <td colspan="8"><b>II. RIWAYAT DIALISIS / TRANSPLANTASI</b></td>
        </tr>
        <tr class="isi">
            <td>Dialisis Pertama :</td>
            <td><input type="date"></td>
            <td>Pernah CPAD :</td>
            <td><select>
                    <option>Tidak</option>
                    <option>Ya</option>
                </select></td>
            <td>Pernah Transplantasi Ginjal :</td>
            <td><select>
                    <option>Tidak</option>
                    <option>Ya</option>
                </select></td>
        </tr>

        <!-- PEMERIKSAAN FISIK -->
        <tr class="isi">
            <td colspan="8"><b>III. PEMERIKSAAN FISIK</b></td>
        </tr>
        <tr class="isi">
            <td>Keadaan Umum :</td>
            <td><select>
                    <option>Sehat</option>
                    <option>Sakit Ringan</option>
                </select></td>
            <td>Kesadaran :</td>
            <td><select>
                    <option>Compos Mentis</option>
                    <option>Somnolen</option>
                </select></td>
            <td>Nadi :</td>
            <td><input type="text" size="5"> x/menit</td>
            <td>BB :</td>
            <td><input type="text" size="5"> Kg</td>
        </tr>
        <tr class="isi">
            <td>TD :</td>
            <td><input type="text" size="5"> mmHg</td>
            <td>Suhu :</td>
            <td><input type="text" size="5"> °C</td>
            <td>Napas :</td>
            <td><input type="text" size="5"> x/menit</td>
            <td>TB :</td>
            <td><input type="text" size="5"> Cm</td>
        </tr>
        <tr class="isi">
            <td>Abdomen :</td>
            <td colspan="3">Hepatomegali: <select>
                    <option>Tidak</option>
                    <option>Ya</option>
                </select> | Splenomegali: <select>
                    <option>Tidak</option>
                    <option>Ya</option>
                </select> | Ascites: <select>
                    <option>Tidak</option>
                    <option>Ya</option>
                </select></td>
            <td>Ekstremitas :</td>
            <td colspan="3">Edema: <select>
                    <option>Tidak</option>
                    <option>Ya</option>
                </select></td>
        </tr>
        <tr class="isi">
            <td>Paru :</td>
            <td colspan="3">Wheezing: <select>
                    <option>Tidak</option>
                    <option>Ya</option>
                </select> | Ronchi: <select>
                    <option>Tidak</option>
                    <option>Ya</option>
                </select></td>
            <td>Jantung :</td>
            <td colspan="3">Kardiomegali: <select>
                    <option>Tidak</option>
                    <option>Ya</option>
                </select> | Bising: <select>
                    <option>Tidak</option>
                    <option>Ya</option>
                </select></td>
        </tr>
        <tr class="isi">
            <td>Konjungtiva :</td>
            <td>Anemia: <select>
                    <option>Tidak</option>
                    <option>Ya</option>
                </select></td>
            <td>Sklera :</td>
            <td>Ikterik: <select>
                    <option>Tidak</option>
                    <option>Ya</option>
                </select></td>
            <td>Tekanan Vena Jugularis (JVP):</td>
            <td><select>
                    <option>Normal</option>
                    <option>Meningkat</option>
                </select></td>
        </tr>

        <!-- PEMERIKSAAN PENUNJANG -->
        <tr class="isi">
            <td colspan="8"><b>IV. PEMERIKSAAN PENUNJANG</b></td>
        </tr>
        <tr class="isi">
            <td>1. Foto Thorax</td>
            <td><input type="date"></td>
            <td>2. EKG</td>
            <td><input type="date"></td>
            <td>3. BNO/IVP</td>
            <td><input type="date"></td>
            <td>4. USG</td>
            <td><input type="date"></td>
        </tr>
        <tr class="isi">
            <td>5. Renogram</td>
            <td><input type="date"></td>
            <td>6. PA Biopsi Ginjal</td>
            <td><input type="date"></td>
            <td>7. CT Scan</td>
            <td><input type="date"></td>
            <td>8. Arteriografi</td>
            <td><input type="date"></td>
        </tr>
        <tr class="isi">
            <td>9. Kultur Urin</td>
            <td><input type="date"></td>
            <td>10. Laboratorium</td>
            <td><input type="date"></td>
        </tr>
        <tr class="isi">
            <td colspan="8">
                <b>Hasil Laboratorium:</b><br>
                Hematokrit: <input type="text"> | Hemoglobin: <input type="text"> | Leukosit: <input type="text"> |
                Trombosit: <input type="text"><br>
                Hitung Jenis: <input type="text"> | Ureum: <input type="text"> | Kreatinin: <input type="text"> | Urin
                Lengkap: <input type="text"><br>
                CCT: <input type="text"> | SGOT: <input type="text"> | SGPT: <input type="text"> | CT/BT: <input
                    type="text"><br>
                Asam Urat: <input type="text"> | HBsAg: <select>
                    <option>Non Reaktif</option>
                    <option>Reaktif</option>
                </select> | Anti HCV: <select>
                    <option>Non Reaktif</option>
                    <option>Reaktif</option>
                </select>
            </td>
        </tr>

        <!-- EDUKASI -->
        <tr class="isi">
            <td colspan="8"><b>V. EDUKASI</b></td>
        <tr class="isi">
            <td colspan="8"><textarea rows="4" cols="150"></textarea></td>
        </tr>

    </table>
</body>

</html>