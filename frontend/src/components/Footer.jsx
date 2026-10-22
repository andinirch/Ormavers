import '../styles/footer.css'

function Footer({ variant = 'content' }) {
  return (
    <footer className={`platform-footer platform-footer--${variant}`}>
      © 2026 Ormavers — Platform Kolaborasi Ormawa Muhammadiyah Jawa Timur.
    </footer>
  )
}

export default Footer
