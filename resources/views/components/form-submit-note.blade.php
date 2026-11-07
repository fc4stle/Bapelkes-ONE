<!-- 
Tambahkan atribut ini ke form:
  <form ... @submit="submitting = true; $el.submit()" x-data="{ submitting: false }">
    <button type="submit" @click="if (submitting) $event.preventDefault()" :disabled="submitting">
      <span x-show="!submitting">Teks Asli</span>
      <span x-show="submitting">Memproses...</span>
    </button>
  </form>

Atau pakai komponen submit-button untuk shorthand.
-->
